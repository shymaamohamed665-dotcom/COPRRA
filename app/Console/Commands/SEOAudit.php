<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\SEO\SEOAuditor;
use App\Services\SEO\SEOAuditReporter;
use App\Services\SEO\SEOIssueFixer;
use App\Services\SEO\SEORouteAuditor;
use App\Services\SEOService;
use Illuminate\Console\Command;

final class SEOAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:audit
                    {--fix : Automatically fix issues where possible}
                    {--model= : Audit specific model (product, category, store)}
                    {--details : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit SEO meta data and fix issues automatically';

    protected SEOService $seoService;

    protected SEOAuditor $seoAuditor;

    protected SEOAuditReporter $reporter;

    protected SEORouteAuditor $routeAuditor;

    protected SEOIssueFixer $issueFixer;

    protected int $totalIssues = 0;

    protected int $fixedIssues = 0;

    /**
     * Execute the console command.
     */
    public function handle(SEOService $seoService): int
    {
        $this->initializeServices($seoService);
        $this->initializeAudit();
        $this->runAudit();
        $this->finalizeAudit();

        return self::SUCCESS;
    }

    /**
     * Initialize the audit process.
     */
    protected function initializeAudit(): void
    {
        $this->info('🔍 Starting SEO Audit...');
        $this->newLine();
    }

    /**
     * Finalize the audit process.
     */
    protected function finalizeAudit(): void
    {
        $this->newLine();
        $this->displaySummary();
    }

    /**
     * Audit all models.
     */
    protected function auditAllModels(): void
    {
        $this->auditModelsByType($this->seoAuditor->getModelMap());
        $this->auditAllRoutes();
    }

    /**
     * Audit all routes.
     */
    protected function auditAllRoutes(): void
    {
        $this->newLine();
        $this->info('🌐 Auditing Routes...');

        $publicRoutes = $this->routeAuditor->getPublicRoutes();
        $this->info('  Found '.count($publicRoutes).' public routes');

        $duplicates = $this->routeAuditor->findDuplicateRoutes($publicRoutes);
        $this->totalIssues += count($duplicates);

        $this->reporter->displayDuplicateRoutes($duplicates, $this->option('details'));
    }

    /**
     * Audit multiple model types.
     *
     * @param  array<string, string>  $modelMap  Map of model classes to their labels
     */
    protected function auditModelsByType(array $modelMap): void
    {
        foreach ($modelMap as $modelClass => $label) {
            $this->auditModels($modelClass, $label);
        }
    }

    /**
     * Audit all instances of a given model.
     */
    protected function auditModels(string $modelClass, string $label): void
    {
        $this->info("📦 Auditing {$label}...");
        $models = $modelClass::all();
        $progressBar = $this->output->createProgressBar($models->count());

        foreach ($models as $model) {
            $this->processAuditResult($this->seoAuditor->auditModel($model));
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Audit specific model.
     */
    protected function auditSpecificModel(string $model): void
    {
        $modelClass = $this->issueFixer->getModelClassFromType($model);

        if ($modelClass !== null) {
            $label = $this->getModelLabel($modelClass);
            $this->auditModels($modelClass, $label);
        } else {
            $this->error("Unknown model: {$model}");
        }
    }

    /**
     * Display audit summary.
     */
    protected function displaySummary(): void
    {
        $this->reporter->displaySummary($this->totalIssues, $this->fixedIssues);
    }

    /**
     * Initialize service dependencies.
     */
    private function initializeServices(SEOService $seoService): void
    {
        $this->seoService = $seoService;
        $this->seoAuditor = new SEOAuditor($seoService);
        $this->reporter = new SEOAuditReporter($this);
        $this->routeAuditor = new SEORouteAuditor;
        $this->issueFixer = new SEOIssueFixer;
    }

    /**
     * Process an audit result.
     */
    private function processAuditResult(\App\Services\SEO\SEOAuditResult $result): void
    {
        if ($result->hasIssues()) {
            $this->totalIssues += $result->getIssueCount();

            if ($this->option('details')) {
                $this->reporter->displayIssueDetails($result);
            }

            if ($this->option('fix')) {
                $this->fixAuditResult($result);
            }
        }
    }

    /**
     * Fix issues found in an audit result.
     */
    private function fixAuditResult(\App\Services\SEO\SEOAuditResult $result): void
    {
        if ($this->issueFixer->fixModelIssues($result->getModel(), $result->getMetaData())) {
            $this->fixedIssues++;

            if ($this->option('details')) {
                $this->reporter->displayFixConfirmation(
                    $result->getModelType(),
                    $result->getModelId()
                );
            }
        }
    }

    /**
     * Get model label from class name.
     */
    private function getModelLabel(string $modelClass): string
    {
        return match ($modelClass) {
            Product::class => 'Products',
            Category::class => 'Categories',
            Store::class => 'Stores',
            default => class_basename($modelClass).'s',
        };
    }

    /**
     * Run the audit based on user options.
     */
    private function runAudit(): void
    {
        $this->shouldAuditSpecificModel()
            ? $this->auditSpecificModel($this->option('model'))
            : $this->auditAllModels();
    }

    /**
     * Check if a specific model should be audited.
     */
    private function shouldAuditSpecificModel(): bool
    {
        return $this->option('model') !== null;
    }
}
