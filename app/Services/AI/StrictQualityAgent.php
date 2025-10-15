<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DataObjects\Ai\Stage;
use App\DataObjects\Ai\StageResult;
use App\Enums\Ai\AgentStage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

final class StrictQualityAgent
{
    /** @var array<string, Stage> */
    private array $stages = [];

    /** @var array<string, StageResult> */
    private array $results = [];

    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, string> */
    private array $fixes = [];

    public function __construct()
    {
        $this->initializeStages();
    }

    /**
     * Execute all quality control stages.
     *
     * @return array{
     *     overall_success: bool,
     *     stages: array<string, StageResult>,
     *     errors: array<string, string>,
     *     fixes: array<string, string>
     * }
     */
    public function executeAllStages(): array
    {
        $this->log('🚀 بدء تنفيذ جميع مراحل ضمان الجودة...');

        $overallSuccess = true;

        foreach ($this->stages as $stageId => $stage) {
            $this->log('📋 تنفيذ المرحلة: '.$stage->name->value);

            $result = $this->executeStage($stageId, $stage);
            $this->results[$stageId] = $result;

            if (! $result->success) {
                $overallSuccess = false;
                $this->log('❌ فشل في المرحلة: '.$stage->name->value);

                if ($stage->strict) {
                    $this->log('🛑 توقف العملية بسبب فشل مرحلة صارمة');
                    break;
                }
            } else {
                $this->log('✅ نجح في المرحلة: '.$stage->name->value);
            }
        }

        $this->generateFinalReport($overallSuccess);

        return [
            'overall_success' => $overallSuccess,
            'stages' => $this->results,
            'errors' => $this->errors,
            'fixes' => $this->fixes,
        ];
    }

    /**
     * Auto-fix issues when possible.
     *
     * @return string[]
     *
     * @psalm-return array{formatting?: 'تم إصلاح تنسيق الكود', dependencies?: 'تم إصلاح التبعيات', caches: 'تم مسح الذاكرة المؤقتة'}
     */
    public function autoFixIssues(): array
    {
        $this->log('🔧 بدء الإصلاح التلقائي للمشاكل...');

        $fixes = [];

        // Fix code formatting
        $this->log('🎨 إصلاح تنسيق الكود...');
        $result = Process::run('./vendor/bin/pint --config=pint.strict.json');
        if ($result->successful()) {
            $fixes['formatting'] = 'تم إصلاح تنسيق الكود';
        }

        // Fix composer issues
        $this->log('📦 إصلاح مشاكل التبعيات...');
        $result = Process::run('composer install --no-dev --optimize-autoloader');
        if ($result->successful()) {
            $fixes['dependencies'] = 'تم إصلاح التبعيات';
        }

        // Clear caches
        $this->log('🗑️ مسح الذاكرة المؤقتة...');
        $commands = [
            'php artisan config:clear',
            'php artisan cache:clear',
            'php artisan route:clear',
            'php artisan view:clear',
        ];

        foreach ($commands as $command) {
            Process::run($command);
        }

        $fixes['caches'] = 'تم مسح الذاكرة المؤقتة';

        $this->fixes = $fixes;

        return $fixes;
    }

    /**
     * Get stage status.
     */
    public function getStageStatus(string $stageId): ?StageResult
    {
        return $this->results[$stageId] ?? null;
    }

    /**
     * Get all results.
     *
     * @return array<string, StageResult>
     */
    public function getAllResults(): array
    {
        return $this->results;
    }

    /**
     * Get errors summary.
     *
     * @return array{
     *     total_errors: int,
     *     errors_by_stage: array<string, string>,
     *     critical_errors: list<string>
     * }
     */
    public function getErrorsSummary(): array
    {
        return [
            'total_errors' => count($this->errors),
            'errors_by_stage' => $this->errors,
            'critical_errors' => array_filter(
                $this->errors,
                static fn (string $error): bool => str_contains($error, 'Fatal')
            ),
        ];
    }

    /**
     * Initialize all quality control stages.
     */
    private function initializeStages(): void
    {
        $this->stages = [
            'syntax_check' => new Stage(
                name: AgentStage::SYNTAX_CHECK,
                command: 'php -l',
                strict: true,
                required: true,
                files: $this->getPhpFiles()
            ),
            'phpstan_analysis' => new Stage(
                name: AgentStage::PHPSTAN_ANALYSIS,
                command: './vendor/bin/phpstan analyse --memory-limit=1G --configuration=phpstan.strict.neon',
                strict: true,
                required: true
            ),
            'phpmd_quality' => new Stage(
                name: AgentStage::PHPMD_QUALITY,
                command: './vendor/bin/phpmd app xml phpmd.strict.xml',
                strict: true,
                required: true
            ),
            'pint_formatting' => new Stage(
                name: AgentStage::PINT_FORMATTING,
                command: './vendor/bin/pint --test --config=pint.strict.json',
                strict: true,
                required: true
            ),
            'composer_audit' => new Stage(
                name: AgentStage::COMPOSER_AUDIT,
                command: 'composer audit',
                strict: true,
                required: true
            ),
            'unit_tests' => new Stage(
                name: AgentStage::UNIT_TESTS,
                command: 'php artisan test tests/Unit/ --configuration=phpunit.strict.xml',
                strict: true,
                required: true
            ),
            'feature_tests' => new Stage(
                name: AgentStage::FEATURE_TESTS,
                command: 'php artisan test tests/Feature/ --configuration=phpunit.strict.xml',
                strict: true,
                required: true
            ),
            'ai_tests' => new Stage(
                name: AgentStage::AI_TESTS,
                command: 'php artisan test tests/AI/ --configuration=phpunit.strict.xml',
                strict: true,
                required: true
            ),
            'security_tests' => new Stage(
                name: AgentStage::SECURITY_TESTS,
                command: 'php artisan test tests/Security/ --configuration=phpunit.strict.xml',
                strict: true,
                required: true
            ),
            'performance_tests' => new Stage(
                name: AgentStage::PERFORMANCE_TESTS,
                command: 'php artisan test tests/Performance/ --configuration=phpunit.strict.xml',
                strict: true,
                required: true
            ),
            'integration_tests' => new Stage(
                name: AgentStage::INTEGRATION_TESTS,
                command: 'php artisan test tests/Integration/ --configuration=phpunit.strict.xml',
                strict: true,
                required: true
            ),
            'e2e_tests' => new Stage(
                name: AgentStage::E2E_TESTS,
                command: 'php artisan dusk --configuration=phpunit.strict.xml',
                strict: true,
                required: true
            ),
            'link_checker' => new Stage(
                name: AgentStage::LINK_CHECKER,
                command: 'php artisan links:check --all',
                strict: true,
                required: true
            ),
        ];
    }

    /**
     * Execute a single stage.
     */
    private function executeStage(string $stageId, Stage $stage): StageResult
    {
        try {
            $startTime = microtime(true);

            $result = $stage->files !== null
                ? $this->executeFileBasedStage($stage)
                : $this->executeCommandStage($stage);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            return new StageResult(
                success: $result['success'],
                output: $result['output'],
                errors: $result['errors'],
                duration: $duration,
                timestamp: now()->toISOString(),
            );
        } catch (\Exception $e) {
            return $this->handleStageException($e, $stageId);
        }
    }

    private function handleStageException(\Exception $e, string $stageId): StageResult
    {
        $this->errors[$stageId] = $e->getMessage();

        return new StageResult(
            success: false,
            output: '',
            errors: [$e->getMessage()],
            duration: 0,
            timestamp: now()->toISOString(),
        );
    }

    /**
     * Execute file-based stage (like syntax check).
     *
     * @return (bool|string|string[])[]
     *
     * @psalm-return array{success: bool, output: 'تم العثور على أخطاء'|'جميع الملفات صحيحة', errors: list<string>}
     */
    private function executeFileBasedStage(Stage $stage): array
    {
        $errors = [];
        $success = true;

        if (is_array($stage->files)) {
            foreach ($stage->files as $file) {
                $this->runFileCommand($stage, $file, $errors, $success);
            }
        }

        return [
            'success' => $success,
            'output' => $success ? 'جميع الملفات صحيحة' : 'تم العثور على أخطاء',
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<string>  $errors
     */
    private function runFileCommand(Stage $stage, string $file, array &$errors, bool &$success): void
    {
        $command = $stage->command.' '.$file;
        $result = Process::run($command);

        if (! $result->successful()) {
            $errors[] = 'خطأ في الملف '.$file.': '.$result->errorOutput();
            $success = false;
        }
    }

    /**
     * Execute command-based stage.
     *
     * @return (bool|string|string[])[]
     *
     * @psalm-return array{success: bool, output: string, errors: list{0?: string}}
     */
    private function executeCommandStage(Stage $stage): array
    {
        $result = Process::run($stage->command);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'errors' => $result->successful() ? [] : [$result->errorOutput()],
        ];
    }

    /**
     * Get all PHP files in the project.
     *
     * @return list<string>
     */
    private function getPhpFiles(): array
    {
        $files = [];
        $directories = ['app', 'config', 'database', 'routes', 'tests'];

        foreach ($directories as $dir) {
            if (File::exists($dir)) {
                $phpFiles = File::allFiles($dir);
                foreach ($phpFiles as $file) {
                    if ($file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Generate comprehensive report.
     */
    private function generateFinalReport(bool $overallSuccess): void
    {
        $report = [
            'timestamp' => now()->toISOString(),
            'overall_success' => $overallSuccess,
            'total_stages' => count($this->stages),
            'successful_stages' => count(array_filter(
                $this->results,
                static fn (StageResult $r): bool => $r->success
            )),
            'failed_stages' => count(array_filter(
                $this->results,
                static fn (StageResult $r): bool => ! $r->success
            )),
            'stages_details' => $this->results,
            'errors' => $this->errors,
            'fixes' => $this->fixes,
        ];

        $reportPath = storage_path('logs/ai-quality-report.json');
        $jsonContent = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonContent !== false) {
            File::put($reportPath, $jsonContent);
        }

        $this->log("📊 تم إنشاء التقرير الشامل: {$reportPath}");
    }

    /**
     * Log messages.
     */
    private function log(string $message): void
    {
        Log::info($message);
        echo $message.PHP_EOL;
    }
}
