<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MySQLConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_handles_mysql_connection_based_on_environment(): void
    {
        $default = (string) config('database.default');

        if ($default === 'mysql') {
            // Should connect successfully when MySQL is the default driver
            $pdo = DB::connection()->getPdo();
            $this->assertNotNull($pdo);
        } else {
            // In testing we expect sqlite memory; any attempt to use MySQL should fail
            $this->expectException(\Throwable::class);
            DB::connection('mysql')->getPdo();
        }
    }
}