<?php

/**
 * Hostinger Deployment Script
 *
 * This script handles deployment to Hostinger shared hosting via FTP/SSH.
 * It uploads build artifacts, runs migrations, and performs post-deployment tasks.
 *
 * Usage:
 * php scripts/deploy-to-hostinger.php [--dry-run] [--skip-db]
 */

declare(strict_types=1);

// Load Laravel environment
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Config;
use phpseclib3\Net\SSH2;

// Configuration
$hostinger = Config::get('hostinger');
$dryRun = in_array('--dry-run', $argv);
$skipDb = in_array('--skip-db', $argv);

echo "Hostinger Deployment Script\n";
echo "==========================\n";
echo 'Dry run: '.($dryRun ? 'YES' : 'NO')."\n";
echo 'Skip DB: '.($skipDb ? 'YES' : 'NO')."\n\n";

// Validate configuration
if (empty($hostinger['ftp']['host']) || empty($hostinger['ftp']['username'])) {
    echo "ERROR: FTP configuration missing in config/hostinger.php\n";
    exit(1);
}

if (! $skipDb && (empty($hostinger['database']['host']) || empty($hostinger['database']['username']))) {
    echo "ERROR: Database configuration missing in config/hostinger.php\n";
    exit(1);
}

/**
 * Recursively upload directory via FTP.
 */
function uploadDirectory($ftpConn, string $localDir, string $remoteDir): void
{
    $files = array_diff(scandir($localDir), ['.', '..']);

    foreach ($files as $file) {
        $localPath = "{$localDir}/{$file}";
        $remotePath = "{$remoteDir}/{$file}";

        if (is_dir($localPath)) {
            // Create directory on remote server if it doesn't exist
            if (! @ftp_chdir($ftpConn, $remotePath)) {
                ftp_mkdir($ftpConn, $remotePath);
            }
            uploadDirectory($ftpConn, $localPath, $remotePath);
        } else {
            if (! ftp_put($ftpConn, $remotePath, $localPath, FTP_BINARY)) {
                throw new Exception("Cannot upload file to {$remotePath}");
            }
        }
    }
}

// Function to build assets
function buildAssets(bool $dryRun): void
{
    echo "Step 1: Building assets...\n";
    if (! $dryRun) {
        exec('npm run build', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new Exception('Asset build failed');
        }
    }
    echo "✓ Assets built successfully\n\n";
}

// Function to prepare the deployment package
function prepareDeploymentPackage(bool $dryRun, array $hostinger, string $deployDir, array $excludePatterns): void
{
    echo "Step 2: Preparing deployment package...\n";
    if (! $dryRun) {
        // Create deployment directory
        if (is_dir($deployDir)) {
            exec("rm -rf {$deployDir}");
        }
        mkdir($deployDir);

        // Copy files excluding patterns
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__.'/../', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $relativePath = str_replace(__DIR__.'/../', '', $file->getPathname());
            $skip = false;

            foreach ($excludePatterns as $pattern) {
                if (fnmatch($pattern, $relativePath) || fnmatch($pattern.'/*', $relativePath)) {
                    $skip = true;
                    break;
                }
            }

            if (! $skip) {
                $targetPath = $deployDir.'/'.$relativePath;
                $targetDir = dirname($targetPath);

                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                if ($file->isFile()) {
                    copy($file->getPathname(), $targetPath);
                }
            }
        }

        // Create .env file for production
        $envContent = file_get_contents(__DIR__.'/../.env.example');
        $envContent = str_replace('APP_ENV=local', 'APP_ENV=production', $envContent);
        $envContent = str_replace('APP_DEBUG=true', 'APP_DEBUG=false', $envContent);
        $envContent = str_replace('DB_HOST=127.0.0.1', 'DB_HOST='.$hostinger['database']['host'], $envContent);
        $envContent = str_replace('DB_DATABASE=laravel', 'DB_DATABASE='.$hostinger['database']['database'], $envContent);
        $envContent = str_replace('DB_USERNAME=root', 'DB_USERNAME='.$hostinger['database']['username'], $envContent);
        $envContent = str_replace('DB_PASSWORD=', 'DB_PASSWORD='.$hostinger['database']['password'], $envContent);
        file_put_contents($deployDir.'/.env', $envContent);
    }
    echo "✓ Deployment package prepared\n\n";
}

// Function to upload files via FTP
function uploadViaFtp(bool $dryRun, array $hostinger, string $deployDir): void
{
    echo "Step 3: Uploading to Hostinger via FTP...\n";
    if (! $dryRun) {
        $ftpConn = ftp_connect($hostinger['ftp']['host']);
        if (! $ftpConn) {
            throw new Exception('FTP connection failed');
        }

        if (! ftp_login($ftpConn, $hostinger['ftp']['username'], $hostinger['ftp']['password'] ?? '')) {
            ftp_close($ftpConn);
            throw new Exception('FTP login failed');
        }

        ftp_pasv($ftpConn, true);

        // Upload files recursively
        uploadDirectory($ftpConn, $deployDir, $hostinger['ftp']['path']);

        ftp_close($ftpConn);
    }
    echo "✓ Files uploaded successfully\n\n";
}

// Function to run remote commands via SSH
function runRemoteCommands(bool $dryRun, bool $skipDb, array $hostinger): void
{
    echo "Step 4: Running remote setup...\n";
    if (! $dryRun) {
        $ssh = new SSH2($hostinger['ssh']['host'], $hostinger['ssh']['port']);
        if (! $ssh->login($hostinger['ssh']['username'], $hostinger['ssh']['password'] ?? '')) {
            throw new Exception('SSH login failed');
        }

        $remotePath = $hostinger['ssh']['path'];

        // Run commands
        $commands = [
            "cd {$remotePath}",
            'php artisan key:generate --force',
            'php artisan config:cache',
            'php artisan route:cache',
            'php artisan view:cache',
            'composer install --no-dev --optimize-autoloader',
        ];

        if (! $skipDb) {
            $commands[] = 'php artisan migrate --force';
            $commands[] = 'php artisan db:seed --force';
        }

        $commands[] = 'php artisan storage:link';
        $commands[] = 'php artisan optimize';

        foreach ($commands as $command) {
            echo "Running: {$command}\n";
            $output = $ssh->exec($command);
            if ($ssh->getExitStatus() !== 0) {
                throw new Exception("Command failed: {$command}\nOutput: {$output}");
            }
            echo $output;
        }
        $ssh->disconnect();
    }
    echo "✓ Remote setup completed\n\n";
}

// Function for post-deployment verification
function verifyDeployment(bool $dryRun, array $hostinger): void
{
    echo "Step 5: Post-deployment verification...\n";
    if (! $dryRun) {
        // Verify files exist
        $ssh = new SSH2($hostinger['ssh']['host'], $hostinger['ssh']['port']);
        if (! $ssh->login($hostinger['ssh']['username'], $hostinger['ssh']['password'] ?? '')) {
            throw new Exception('SSH login failed for verification');
        }

        $checkCommands = [
            "cd {$hostinger['ssh']['path']} && ls -la",
            "cd {$hostinger['ssh']['path']} && php artisan --version",
        ];

        foreach ($checkCommands as $command) {
            $output = $ssh->exec($command);
            if ($ssh->getExitStatus() !== 0) {
                echo "Verification failed: {$command}\n";
            }
        }
        $ssh->disconnect();
    }
    echo "✓ Verification completed\n\n";
}

// Function to clean up deployment artifacts
function cleanup(bool $dryRun, string $deployDir): void
{
    if (! $dryRun && is_dir($deployDir)) {
        exec("rm -rf {$deployDir}");
    }
}

try {
    // Step 1: Build assets locally
    buildAssets($dryRun);

    // Step 2: Prepare deployment package
    $deployDir = __DIR__.'/../.deploy';
    $excludePatterns = [
        '.git',
        'node_modules',
        'tests',
        '.env*',
        '*.log',
        'storage/logs/*',
        'storage/framework/cache/*',
        'storage/framework/sessions/*',
        'storage/framework/views/*',
        'bootstrap/cache/*',
        'vendor',
        '.deploy',
        'docs',
        'reports',
        'dev-docker',
        'docker',
        'scripts',
        '.github',
        '.husky',
        '.vscode',
        '.phpunit.cache',
        '.qodo',
        'infection.json.dist',
        'phpmd.xml',
        'phpstan*',
        'psalm.xml',
        'pint.json',
        'audit.ps1',
        'project-self-test.ps1',
        'test_*',
        'TODO.md',
        'FEATURES.md',
        'FINAL_COMPREHENSIVE_AUDIT_REPORT.md',
        'README_PERFORMANCE.md',
        'build',
        'storage/app/*',
        'storage/backups/*',
    ];
    prepareDeploymentPackage($dryRun, $hostinger, $deployDir, $excludePatterns);

    // Step 3: Upload via FTP
    uploadViaFtp($dryRun, $hostinger, $deployDir);

    // Step 4: Run remote commands via SSH
    runRemoteCommands($dryRun, $skipDb, $hostinger);

    // Step 5: Post-deployment verification
    verifyDeployment($dryRun, $hostinger);

    // Cleanup
    cleanup($dryRun, $deployDir);

    echo "🎉 Deployment to Hostinger successful!\n";
    exit(0);
} catch (Exception $e) {
    echo '❌ Deployment failed: '.$e->getMessage()."\n";
    exit(1);
}
