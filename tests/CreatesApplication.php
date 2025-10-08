<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        // تعيين APP_KEY قبل إنشاء التطبيق
        putenv('APP_KEY=base64:mAkbpuXF7OVTRIDCIMkD8+xw6xVi7pge9CFImeqZaxE=');

        // Force the test database connection BEFORE creating the application
        putenv('DB_CONNECTION=testing');
        putenv('DB_DATABASE=:memory:');
        putenv('DB_HOST=');
        putenv('DB_PORT=');
        putenv('DB_USERNAME=');
        putenv('DB_PASSWORD=');

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Force the test database connection AFTER bootstrap
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        config(['database.connections.sqlite.driver' => 'sqlite']);
        config(['database.connections.sqlite.prefix' => '']);
        config(['database.connections.sqlite.foreign_key_constraints' => true]);

        // Clear any cached configuration
        if (is_object($app) && method_exists($app, 'make')) {
            try {
                $app->make(\Illuminate\Contracts\Console\Kernel::class)->call('config:clear');
            } catch (\Exception $e) {
                // Ignore if config:clear fails
            }
        }

        // Ensure database is properly configured for testing
        if ($app->environment('testing')) {
            $app->useDatabasePath(':memory:');
        }

        // Load configuration for testing
        $app->make('config')->set('database.default', 'testing');
        $app->make('config')->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Bind silent mocks for console input and output to prevent interactive prompts during tests
        $app->bind(\Symfony\Component\Console\Input\InputInterface::class, function ($app) {
            /** @var \Mockery\MockInterface&\Symfony\Component\Console\Input\InputInterface $mock */
            $mock = \Mockery::mock(\Symfony\Component\Console\Input\InputInterface::class);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('isInteractive')->andReturn(false);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('hasArgument')->andReturn(false);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('getArgument')->andReturn(null);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('hasOption')->andReturn(false);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('getOption')->andReturn(null);

            return $mock;
        });

        // Use a lenient mock for the output style to ignore unexpected calls like askQuestion
        $app->bind(\Symfony\Component\Console\Style\OutputStyle::class, function ($app) {
            /** @var \Mockery\MockInterface&\Symfony\Component\Console\Style\SymfonyStyle $mock */
            $mock = \Mockery::mock(\Symfony\Component\Console\Style\SymfonyStyle::class);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('askQuestion')->andReturn(true);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('confirm')->andReturn(true);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('ask')->andReturn('test');
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('choice')->andReturn('test');
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('writeln')->andReturn(null);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('write')->andReturn(null);
            /* @phpstan-ignore-next-line */
            $mock->shouldReceive('newLine')->andReturn(null);

            return $mock;
        });

        return $app;
    }
}
