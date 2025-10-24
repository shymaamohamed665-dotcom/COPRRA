<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class GenerateMigrationsFromFixSql extends Command
{
    protected $signature = 'generate:migrations-from-fix-sql {--sql=} {--reports=} {--dry-run}';

    protected $description = 'Parse reports/fix-suggestions.sql and generate Laravel migrations (indexes first, then column changes).';

    public function handle(): int
    {
        $sqlPath = $this->option('sql') ?: base_path('reports/fix-suggestions.sql');
        $reportsDir = $this->option('reports') ?: base_path('reports');
        $dryRun = (bool) $this->option('dry-run');

        $altCandidates = [
            $sqlPath,
            base_path('downloaded-ci-test-results/reports/fix-suggestions.sql'),
            base_path('ci-test-results/reports/fix-suggestions.sql'),
        ];

        $existing = null;
        foreach ($altCandidates as $candidate) {
            if (File::exists($candidate)) {
                $existing = $candidate;
                break;
            }
        }

        if (! $existing) {
            $this->error('fix-suggestions.sql not found. Checked:');
            foreach ($altCandidates as $c) {
                $this->line(' - '.$c);
            }
            $this->line('Provide the file via --sql=path or place it under reports/.');

            return 1;
        }

        $this->info('Parsing: '.$existing);
        $sql = File::get($existing);

        // Parse operations
        $indexOps = $this->parseAddIndexStatements($sql); // [ [table, index, columns[]] ]
        $modifyOps = $this->parseModifyColumnStatements($sql); // [ [table, column, def, charset, collate] ]

        if (empty($indexOps) && empty($modifyOps)) {
            $this->warn('No ADD INDEX or MODIFY COLUMN statements found to convert.');

            return 0;
        }

        $this->line('Parsed index ops count: '.count($indexOps));
        if (! empty($indexOps)) {
            $this->line('First index op sample: '.json_encode($indexOps[0]));
        }

        // Group by table
        $indexesByTable = [];
        foreach ($indexOps as $op) {
            if (! is_array($op) || ! isset($op['table'], $op['index'], $op['columns'])) {
                continue;
            }
            $indexesByTable[$op['table']][] = $op;
        }

        $modsByTable = [];
        foreach ($modifyOps as $op) {
            $modsByTable[$op['table']][] = $op;
        }

        // Build original type map from mismatch reports if available (for down())
        $originalTypes = $this->buildOriginalTypesMap($reportsDir);

        // Generate index migrations first
        foreach ($indexesByTable as $table => $ops) {
            $name = 'add_indexes_to_'.$table.'_table_from_fix_sql';
            $this->generateMigration($name, function (string $path) use ($table, $ops) {
                $content = $this->renderIndexMigration($table, $ops);
                File::put($path, $content);
            }, $dryRun);
        }

        // Then generate column type migrations
        foreach ($modsByTable as $table => $ops) {
            $name = 'align_column_types_for_'.$table.'_table_from_fix_sql';
            $this->generateMigration($name, function (string $path) use ($table, $ops, $originalTypes) {
                $content = $this->renderModifyMigration($table, $ops, $originalTypes);
                File::put($path, $content);
            }, $dryRun);
        }

        $this->info('Completed migration generation.');

        return 0;
    }

    /**
     * @return array<int, array{table:string,index:string,columns:array<int,string>}>
     */
    private function parseAddIndexStatements(string $sql): array
    {
        $pattern = '/ALTER\s+TABLE\s+`(?<table>[^`]+)`\s+ADD\s+INDEX\s+`(?<index>[^`]+)`\s*\((?<columns>[^\)]+)\)\s*;/i';
        $matches = [];
        preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER);
        $ops = [];
        foreach ($matches as $m) {
            $colsRaw = $m['columns'];
            $cols = [];
            foreach (explode(',', $colsRaw) as $c) {
                $c = trim($c);
                $c = trim($c, '` ');
                if ($c !== '') {
                    $cols[] = $c;
                }
            }
            $ops[] = [
                'table' => $m['table'],
                'index' => $m['index'],
                'columns' => $cols,
            ];
        }

        return $ops;
    }

    /**
     * @return array<int, array{table:string,column:string,definition:string,charset:?string,collate:?string}>
     */
    private function parseModifyColumnStatements(string $sql): array
    {
        $pattern = '/ALTER\s+TABLE\s+`(?<table>[^`]+)`\s+MODIFY\s+`(?<column>[^`]+)`\s+(?<definition>[^;]+?)(?:\s*;|$)/i';
        $matches = [];
        preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER);
        $ops = [];
        foreach ($matches as $m) {
            $def = trim($m['definition']);
            $charset = null;
            $collate = null;
            if (preg_match('/character\s+set\s+(?<cs>\w+)/i', $def, $cm)) {
                $charset = $cm['cs'];
            }
            if (preg_match('/collate\s+(?<co>\w+)/i', $def, $clm)) {
                $collate = $clm['co'];
            }
            $ops[] = [
                'table' => $m['table'],
                'column' => $m['column'],
                'definition' => $def,
                'charset' => $charset,
                'collate' => $collate,
            ];
        }

        return $ops;
    }

    /**
     * Attempt to build a map of original column types from reports for down().
     * Format: [table][column] => originalDefinition
     *
     * @return array<string, array<string, string>>
     */
    private function buildOriginalTypesMap(string $reportsDir): array
    {
        $map = [];
        $candidates = [
            $reportsDir.'/mysql-fk-type-mismatches.txt',
            $reportsDir.'/mysql-fk-unsigned-mismatches.txt',
        ];
        foreach ($candidates as $path) {
            if (! File::exists($path)) {
                continue;
            }
            $lines = preg_split("/\r?\n/", File::get($path)) ?: [];
            foreach ($lines as $line) {
                // Expect tab or pipe-delimited entries; try to detect child table/column/type
                // Heuristic: child_table, child_column, child_coltype present in line
                if (preg_match('/child_table\s*[:=]\s*(?<ct>[\w`]+)/i', $line, $mCt)
                    && preg_match('/child_column\s*[:=]\s*(?<cc>[\w`]+)/i', $line, $mCc)
                    && preg_match('/child_coltype\s*[:=]\s*(?<cty>[^,|]+)$/i', $line, $mTy)
                ) {
                    $table = trim(str_replace('`', '', $mCt['ct']));
                    $col = trim(str_replace('`', '', $mCc['cc']));
                    $type = trim($mTy['cty']);
                    $map[$table][$col] = $type;

                    continue;
                }
                // Generic CSV-like format: table,column,child_type,parent_type
                $parts = array_map('trim', preg_split('/\s*[\t|,]\s*/', $line) ?: []);
                if (count($parts) >= 3) {
                    $table = trim(str_replace('`', '', $parts[0]));
                    $col = trim(str_replace('`', '', $parts[1]));
                    $type = $parts[2];
                    if ($table !== '' && $col !== '' && $type !== '') {
                        $map[$table][$col] = $type;
                    }
                }
            }
        }

        return $map;
    }

    private function generateMigration(string $name, \Closure $writer, bool $dryRun): void
    {
        if ($dryRun) {
            $this->line('[dry-run] Would create migration: '.$name);

            return;
        }
        Artisan::call('make:migration', ['name' => $name]);
        // Find created file
        $pattern = '/_'.preg_quote($name, '/').'\.php$/';
        $dir = base_path('database/migrations');
        $files = File::files($dir);
        $target = null;
        // pick the most recent matching file
        foreach ($files as $file) {
            $fname = $file->getFilename();
            if (preg_match($pattern, $fname)) {
                $target = $file->getPathname();
            }
        }
        if (! $target) {
            $this->error('Failed to locate generated migration for '.$name);

            return;
        }
        $writer($target);
        $this->info('Generated migration: '.$target);
    }

    /**
     * Render migration PHP content for index operations.
     *
     * @param  array<int, array{table:string,index:string,columns:array<int,string>}>  $ops
     */
    private function renderIndexMigration(string $table, array $ops): string
    {
        $upBody = '';
        $downBody = '';
        foreach ($ops as $op) {
            if (! is_array($op)) {
                continue;
            }
            if (! isset($op['columns'], $op['index'])) {
                continue;
            }
            $colsArray = var_export($op['columns'], true);
            $upBody .= '            $table->index('.$colsArray.", '".$op['index']."');\n";
            $downBody .= '            $table->dropIndex(\''.$op['index'].'\');'."\n";
        }

        $tpl = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table): void {
__UP__
        });
    }

    public function down(): void
    {
        Schema::table('__TABLE__', function (Blueprint $table): void {
__DOWN__
        });
    }
};
PHP;

        return str_replace(['__TABLE__', '__UP__', '__DOWN__'], [$table, rtrim($upBody), rtrim($downBody)], $tpl);
    }

    /**
     * Render migration PHP content for column modifications.
     *
     * @param  array<int, array{table:string,column:string,definition:string,charset:?string,collate:?string}>  $ops
     * @param  array<string, array<string, string>>  $originalTypes
     */
    private function renderModifyMigration(string $table, array $ops, array $originalTypes): string
    {
        $upSchema = '';
        $upRaw = '';
        $downSchema = '';
        $downRaw = '';

        foreach ($ops as $op) {
            $col = $op['column'];
            $def = $op['definition'];
            $parsed = $this->parseColumnDefinition($def);

            if ($parsed['schema']) {
                $upSchema .= '            '.$parsed['schema']."\n";
            } else {
                $upRaw .= "        DB::statement(\"ALTER TABLE `{$table}` MODIFY `{$col}` {$def}\");\n";
            }
            if ($op['charset'] || $op['collate']) {
                // Ensure charset/collation explicitly applied via raw SQL
                $upRaw .= "        DB::statement(\"ALTER TABLE `{$table}` MODIFY `{$col}` {$def}\");\n";
            }

            $orig = $originalTypes[$table][$col] ?? null;
            if ($orig) {
                // Try to reverse via schema builder, else raw
                $parsedDown = $this->parseColumnDefinition($orig);
                if ($parsedDown['schema']) {
                    $downSchema .= '            '.$parsedDown['schema']."\n";
                } else {
                    $downRaw .= "        DB::statement(\"ALTER TABLE `{$table}` MODIFY `{$col}` {$orig}\");\n";
                }
            } else {
                $downRaw .= "        // Original type unknown; skipping reversal for `{$col}` to avoid data loss.\n";
            }
        }

        $upBody = '';
        if ($upSchema !== '') {
            $upBody .= "        Schema::table('{$table}', function (\Illuminate\\Database\\Schema\\Blueprint \$table): void {\n".$upSchema."        });\n";
        }
        if ($upRaw !== '') {
            $upBody .= rtrim($upRaw)."\n";
        }

        $downBody = '';
        if ($downSchema !== '') {
            $downBody .= "        Schema::table('{$table}', function (\Illuminate\\Database\\Schema\\Blueprint \$table): void {\n".$downSchema."        });\n";
        }
        if ($downRaw !== '') {
            $downBody .= rtrim($downRaw)."\n";
        }

        $tpl = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
__UP__
    }

    public function down(): void
    {
__DOWN__
    }
};
PHP;

        return str_replace(['__UP__', '__DOWN__'], [rtrim($upBody), rtrim($downBody)], $tpl);
    }

    /**
     * Parse a MySQL column definition into a Laravel Schema Builder call where possible.
     *
     * @return array{schema: ?string}
     */
    private function parseColumnDefinition(string $def): array
    {
        $def = trim($def);
        // Extract type(length[,scale]) and flags
        if (! preg_match('/^(?<type>\w+)(?:\((?<len>[\d,]+)\))?(?<rest>.*)$/i', $def, $m)) {
            return ['schema' => null];
        }
        $type = strtolower($m['type']);
        $len = (string) $m['len'];
        $rest = strtolower((string) $m['rest']);

        $unsigned = Str::contains($rest, 'unsigned');
        $nullable = Str::contains($rest, 'null') && ! Str::contains($rest, 'not null');
        $default = null;
        if (preg_match('/default\s+([^\s]+)/i', $rest, $dm)) {
            $default = $dm[1];
        }

        $colPlaceholder = '__COL__';
        $call = null;
        switch ($type) {
            case 'bigint':
                $call = $unsigned ? "\$table->unsignedBigInteger('{$colPlaceholder}')->change();" : "\$table->bigInteger('{$colPlaceholder}')->change();";
                break;
            case 'int':
            case 'integer':
                $call = $unsigned ? "\$table->unsignedInteger('{$colPlaceholder}')->change();" : "\$table->integer('{$colPlaceholder}')->change();";
                break;
            case 'smallint':
                $call = $unsigned ? "\$table->unsignedSmallInteger('{$colPlaceholder}')->change();" : "\$table->smallInteger('{$colPlaceholder}')->change();";
                break;
            case 'mediumint':
                $call = $unsigned ? null : "\$table->mediumInteger('{$colPlaceholder}')->change();"; // Laravel has no unsigned mediumint
                break;
            case 'tinyint':
                $call = $unsigned ? "\$table->unsignedTinyInteger('{$colPlaceholder}')->change();" : "\$table->tinyInteger('{$colPlaceholder}')->change();";
                break;
            case 'varchar':
                $length = (int) ($len !== '' ? explode(',', $len)[0] : 255);
                $call = "\$table->string('{$colPlaceholder}', {$length})->change();";
                break;
            case 'char':
                $length = (int) ($len !== '' ? explode(',', $len)[0] : 255);
                $call = "\$table->char('{$colPlaceholder}', {$length})->change();";
                break;
            case 'text':
                $call = "\$table->text('{$colPlaceholder}')->change();";
                break;
            case 'mediumtext':
                $call = "\$table->mediumText('{$colPlaceholder}')->change();";
                break;
            case 'longtext':
                $call = "\$table->longText('{$colPlaceholder}')->change();";
                break;
            case 'decimal':
                $parts = $len !== '' ? explode(',', $len) : [10, 0];
                $precision = (int) $parts[0];
                $scale = (int) ($parts[1] ?? 0);
                // Laravel decimal() has no unsigned variant
                $call = "\$table->decimal('{$colPlaceholder}', {$precision}, {$scale})->change();";
                break;
            case 'datetime':
                $call = "\$table->dateTime('{$colPlaceholder}')->change();";
                break;
            case 'timestamp':
                $call = "\$table->timestamp('{$colPlaceholder}')->change();";
                break;
            case 'date':
                $call = "\$table->date('{$colPlaceholder}')->change();";
                break;
            default:
                $call = null;
        }

        if (! $call) {
            return ['schema' => null];
        }

        if ($nullable) {
            $call = str_replace(')->change();', ')->nullable()->change();', $call);
        }
        if ($default) {
            // naive default mapping; quotes preserved if present
            $call = str_replace(')->change();', ")->default({$default})->change();", $call);
        }

        return ['schema' => $call];
    }
}
