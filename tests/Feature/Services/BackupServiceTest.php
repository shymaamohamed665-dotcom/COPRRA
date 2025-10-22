<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\BackupService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Mockery;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    private BackupService $service;

    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPath = storage_path('backups');

        // Mock config before creating service
        Config::shouldReceive('get')
            ->with('database.connections.mysql', null)
            ->andReturn([
                'host' => 'localhost',
                'port' => '3306',
                'username' => 'root',
                'password' => 'password',
                'database' => 'test_db',
            ]);

        // Ø§Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø­Ø§ÙˆÙŠØ© Ù„Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ø®Ø¯Ù…Ø© Ù…Ø¹ Ø­Ù‚Ù† Ø§Ù„Ø§Ø¹ØªÙ…Ø§Ø¯ÙŠØ§Øª ØªÙ„Ù‚Ø§Ø¦ÙŠÙ‹Ø§
        $this->service = $this->app->make(BackupService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creates_full_backup_successfully()
    {
        // Arrange
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "full_backup_{$timestamp}";
        $backupDir = $this->backupPath.'/'.$backupName;

        // Create backup directory
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0o755, true);
        }

        // Mock Process facade to actually create the database file and archive
        Process::shouldReceive('run')
            ->andReturnUsing(function ($command) {
                // Extract the output file from the mysqldump command
                if (preg_match('/mysqldump.*> (.+)/', $command, $matches)) {
                    $outputFile = $matches[1];
                    file_put_contents($outputFile, '-- Dummy database content');
                }
                // Handle tar command for compression
                elseif (preg_match('/tar -czf ([^\s]+)/', $command, $matches)) {
                    $archiveFile = $matches[1];
                    file_put_contents($archiveFile, '-- Dummy compressed archive');
                }

                return new class
                {
                    public function successful()
                    {
                        return true;
                    }
                };
            });

        // Mock file operations to create dummy files
        $this->mockFileOperations($backupDir);

        Log::shouldReceive('info')
            ->with('Starting full backup', Mockery::type('array'));

        Log::shouldReceive('info')
            ->with('Full backup completed', Mockery::type('array'));

        // Mock any error logs that might be called
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->with(Mockery::type('string'), Mockery::type('array'));

        // Act
        $result = $this->service->createFullBackup();

        // Assert
        $this->assertStringStartsWith('full_backup_', $result['backup_name']);
        $this->assertEquals('completed', $result['status']);
        $this->assertArrayHasKey('started_at', $result);
        $this->assertArrayHasKey('completed_at', $result);
        $this->assertArrayHasKey('components', $result);
        $this->assertArrayHasKey('size', $result);

        // Verify that logging was called for backup process
        Log::shouldHaveReceived('info')->with('Starting full backup', Mockery::type('array'))->once();
        Log::shouldHaveReceived('info')->with('Full backup completed', Mockery::type('array'))->once();

        // Verify that Process::run was called for database dump
        Process::shouldHaveReceived('run')->with(Mockery::pattern('/mysqldump/'))->once();

        // Verify that Process::run was called for compression
        Process::shouldHaveReceived('run')->with(Mockery::pattern('/tar -czf/'))->once();

        // Verify backup directory was created (check if any backup directory exists)
        $this->assertTrue(is_dir($this->backupPath), 'Backup base directory should exist');

        // Verify backup contains expected components
        $this->assertIsArray($result['components']);
        $this->assertGreaterThan(0, count($result['components']));

        // Cleanup
        if (is_dir($backupDir)) {
            $this->deleteDirectory($backupDir);
        }
    }

    public function test_handles_full_backup_exception()
    {
        // Arrange
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "full_backup_{$timestamp}";

        Log::shouldReceive('info')
            ->with('Starting full backup', ['backup_name' => $backupName]);

        // Mock Process to fail
        Process::shouldReceive('run')
            ->andReturnSelf();
        Process::shouldReceive('successful')
            ->andReturn(false);
        Process::shouldReceive('errorOutput')
            ->andReturn('Database backup failed');

        Log::shouldReceive('error')
            ->with('Full backup failed', Mockery::type('array'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->service->createFullBackup();
    }

    public function test_creates_database_backup_successfully()
    {
        // Arrange
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "database_backup_{$timestamp}";
        $backupDir = $this->backupPath.'/'.$backupName;

        // Create backup directory
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0o755, true);
        }

        // Mock Process facade to actually create the database file
        Process::shouldReceive('run')
            ->andReturnUsing(function ($command) {
                // Extract the output file from the mysqldump command
                if (preg_match('/mysqldump.*> (.+)/', $command, $matches)) {
                    $outputFile = $matches[1];
                    file_put_contents($outputFile, '-- Dummy database content');
                }

                return new class
                {
                    public function successful()
                    {
                        return true;
                    }
                };
            });

        // Create backup directory first
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0o755, true);
        }

        Log::shouldReceive('info')
            ->with('Starting database backup', Mockery::type('array'));

        Log::shouldReceive('info')
            ->with('Database backup completed', Mockery::type('array'));

        // Mock any error logs that might be called
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->with(Mockery::type('string'), Mockery::type('array'));

        // Act
        $result = $this->service->createDatabaseBackup();

        // Assert
        $this->assertEquals($backupName, $result['backup_name']);
        $this->assertEquals('completed', $result['status']);
        $this->assertArrayHasKey('completed_at', $result);

        // Verify that logging was called for database backup process
        Log::shouldHaveReceived('info')->with('Starting database backup', Mockery::type('array'))->once();
        Log::shouldHaveReceived('info')->with('Database backup completed', Mockery::type('array'))->once();

        // Verify that Process::run was called for mysqldump
        Process::shouldHaveReceived('run')->with(Mockery::pattern('/mysqldump/'))->once();

        // Verify backup directory was created (check if any backup directory exists)
        $this->assertTrue(is_dir($this->backupPath), 'Backup base directory should exist');

        // Verify the backup name follows expected format
        $this->assertStringStartsWith('database_backup_', $result['backup_name']);

        // Cleanup
        if (is_dir($backupDir)) {
            $this->deleteDirectory($backupDir);
        }
    }

    public function test_creates_files_backup_successfully()
    {
        // Arrange
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "files_backup_{$timestamp}";
        $backupDir = $this->backupPath.'/'.$backupName;

        // Create backup directory
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0o755, true);
        }

        // Mock Process facade to handle tar compression without relying on OS tools
        Process::shouldReceive('run')
            ->andReturnUsing(function ($command) {
                if (preg_match('/tar -czf ([^\s]+)/', $command, $matches)) {
                    $archiveFile = $matches[1];
                    file_put_contents($archiveFile, '-- Dummy compressed archive');
                }

                return new class
                {
                    public function successful()
                    {
                        return true;
                    }
                };
            });

        Log::shouldReceive('info')
            ->with('Starting files backup', ['backup_name' => $backupName]);

        Log::shouldReceive('info')
            ->with('Files backup completed', Mockery::type('array'));

        // Mock any error logs that might be called
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->with(Mockery::type('string'), Mockery::type('array'));

        // Act
        $result = $this->service->createFilesBackup();

        // Assert
        $this->assertEquals($backupName, $result['backup_name']);
        $this->assertEquals('completed', $result['status']);
        $this->assertArrayHasKey('completed_at', $result);

        // Verify compression command was attempted
        Process::shouldHaveReceived('run')->with(Mockery::pattern('/tar -czf/'));

        // Cleanup
        if (is_dir($backupDir)) {
            $this->deleteDirectory($backupDir);
        }
    }

    public function test_restores_from_backup_successfully()
    {
        // Arrange
        $backupName = 'test_backup';
        $backupPath = $this->backupPath.'/'.$backupName;

        // Create backup directory and manifest
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0o755, true);
        }
        if (! is_dir($backupPath)) {
            mkdir($backupPath, 0o755, true);
        }

        // Create a test manifest
        $manifest = [
            'type' => 'full_backup',
            'created_at' => now()->toISOString(),
            'components' => [
                'database' => ['filename' => 'database.sql'],
                'files' => ['directories' => ['storage/app']],
                'config' => ['files' => ['.env']],
            ],
        ];
        file_put_contents($backupPath.'/manifest.json', json_encode($manifest));

        // Create the database file
        file_put_contents($backupPath.'/database.sql', '-- Dummy database content');

        // Create the files directory
        mkdir($backupPath.'/files', 0o755, true);

        // Create the config directory
        mkdir($backupPath.'/config', 0o755, true);

        // Mock Process facade
        Process::shouldReceive('run')
            ->andReturnSelf();
        Process::shouldReceive('successful')
            ->andReturn(true);

        Log::shouldReceive('info')
            ->with('Starting restore from backup', ['backup_name' => $backupName]);

        Log::shouldReceive('info')
            ->with('Restore completed', Mockery::type('array'));

        // Mock any error logs that might be called
        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->with(Mockery::type('string'), Mockery::type('array'));

        // Act
        $result = $this->service->restoreFromBackup($backupName);

        // Assert
        $this->assertEquals($backupName, $result['backup_name']);
        $this->assertEquals('completed', $result['status']);
        $this->assertArrayHasKey('started_at', $result);
        $this->assertArrayHasKey('completed_at', $result);
        $this->assertArrayHasKey('components', $result);

        // Cleanup
        if (is_dir($backupPath)) {
            $this->deleteDirectory($backupPath);
        }
    }

    public function test_handles_restore_with_nonexistent_backup()
    {
        // Arrange
        $backupName = 'nonexistent_backup';

        Log::shouldReceive('info')
            ->with('Starting restore from backup', ['backup_name' => $backupName]);

        Log::shouldReceive('error')
            ->with('Restore failed', Mockery::type('array'));

        // Act & Assert
        $this->expectException(Exception::class);
        $this->service->restoreFromBackup($backupName);
    }

    public function test_lists_backups_successfully()
    {
        // Arrange - clean up any existing backups first
        if (is_dir($this->backupPath)) {
            $this->deleteDirectory($this->backupPath);
        }

        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0o755, true);
        }

        // Create test backup directories
        $backup1Path = $this->backupPath.'/backup_1';
        $backup2Path = $this->backupPath.'/backup_2';

        mkdir($backup1Path, 0o755, true);
        mkdir($backup2Path, 0o755, true);

        // Create manifests
        $manifest1 = ['type' => 'full_backup', 'created_at' => '2023-01-01T00:00:00Z'];
        $manifest2 = ['type' => 'database_backup', 'created_at' => '2023-01-02T00:00:00Z'];

        file_put_contents($backup1Path.'/manifest.json', json_encode($manifest1));
        file_put_contents($backup2Path.'/manifest.json', json_encode($manifest2));

        // Act
        $result = $this->service->listBackups();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('backup_2', $result[0]['name']); // Should be sorted by date desc
        $this->assertEquals('backup_1', $result[1]['name']);

        // Cleanup
        $this->deleteDirectory($backup1Path);
        $this->deleteDirectory($backup2Path);
    }

    public function test_returns_empty_list_when_no_backup_directory()
    {
        // Arrange - ensure backup directory doesn't exist
        if (is_dir($this->backupPath)) {
            $this->deleteDirectory($this->backupPath);
        }

        // Act
        $result = $this->service->listBackups();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_deletes_backup_successfully()
    {
        // Arrange
        $backupName = 'test_backup';
        $backupPath = $this->backupPath.'/'.$backupName;

        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0o755, true);
        }
        mkdir($backupPath, 0o755, true);

        Log::shouldReceive('info')
            ->with('Backup deleted', ['backup_name' => $backupName]);

        // Act
        $result = $this->service->deleteBackup($backupName);

        // Assert
        $this->assertTrue($result);
    }

    public function test_handles_delete_nonexistent_backup()
    {
        // Arrange
        $backupName = 'nonexistent_backup';

        Log::shouldReceive('error')
            ->with('Failed to delete backup', Mockery::type('array'));

        // Act
        $result = $this->service->deleteBackup($backupName);

        // Assert
        $this->assertFalse($result);
    }

    public function test_cleans_old_backups()
    {
        // Arrange
        $daysOld = 30;

        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0o755, true);
        }

        // Create old backup directories
        $oldBackup1Path = $this->backupPath.'/old_backup_1';
        $oldBackup2Path = $this->backupPath.'/old_backup_2';

        mkdir($oldBackup1Path, 0o755, true);
        mkdir($oldBackup2Path, 0o755, true);

        // Create manifests with old dates
        $oldDate = now()->subDays(35)->toISOString();
        $manifest1 = ['type' => 'full_backup', 'created_at' => $oldDate];
        $manifest2 = ['type' => 'database_backup', 'created_at' => $oldDate];

        file_put_contents($oldBackup1Path.'/manifest.json', json_encode($manifest1));
        file_put_contents($oldBackup2Path.'/manifest.json', json_encode($manifest2));

        // Mock the listBackups method to return our test backups
        $this->service = Mockery::mock(BackupService::class)->makePartial();
        $this->service->shouldReceive('listBackups')
            ->andReturn([
                [
                    'name' => 'old_backup_1',
                    'created_at' => $oldDate,
                    'type' => 'full_backup',
                ],
                [
                    'name' => 'old_backup_2',
                    'created_at' => $oldDate,
                    'type' => 'database_backup',
                ],
            ]);

        // Mock deleteBackup to return true
        $this->service->shouldReceive('deleteBackup')
            ->with('old_backup_1')
            ->andReturn(true);
        $this->service->shouldReceive('deleteBackup')
            ->with('old_backup_2')
            ->andReturn(true);

        Log::shouldReceive('info')
            ->with('Old backups cleaned', Mockery::type('array'));

        Log::shouldReceive('error')
            ->zeroOrMoreTimes()
            ->with(Mockery::type('string'), Mockery::type('array'));

        // Act
        $result = $this->service->cleanOldBackups($daysOld);

        // Assert
        $this->assertEquals(2, $result);

        // Cleanup
        if (is_dir($oldBackup1Path)) {
            $this->deleteDirectory($oldBackup1Path);
        }
        if (is_dir($oldBackup2Path)) {
            $this->deleteDirectory($oldBackup2Path);
        }
    }

    // Helper methods

    private function mockFileOperations(string $backupDir): void
    {
        // Create necessary directories and files for the backup
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0o755, true);
        }

        // Create database file
        $dbFile = $backupDir.'/database.sql';
        file_put_contents($dbFile, '-- Dummy database content');

        // Don't create files or config directories - let the service create them
    }

    protected function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $ignore = ['.gitkeep', '.DS_Store', 'Thumbs.db'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if (! ($item instanceof \SplFileInfo)) {
                continue;
            }

            $path = $item->getPathname();
            $basename = $item->getBasename();

            if ($item->isDir()) {
                @rmdir($path);
                continue;
            }

            if (in_array($basename, $ignore, true)) {
                continue;
            }

            if (PHP_OS_FAMILY === 'Windows') {
                @chmod($path, 0o666);
            }

            @unlink($path);
        }

        @rmdir($dir);
    }
}
