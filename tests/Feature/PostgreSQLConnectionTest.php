<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostgreSQLConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_handles_pgsql_connection_based_on_environment(): void
    {
        $default = (string) config('database.default');

        if ($default === 'pgsql' || $default === 'postgres' || $default === 'postgresql') {
            // Should connect successfully when PostgreSQL is the default driver
            $pdo = DB::connection()->getPdo();
            $this->assertNotNull($pdo);
        } else {
            // In testing we expect sqlite memory; any attempt to use PostgreSQL should fail
            $this->expectException(\Throwable::class);
            DB::connection('pgsql')->getPdo();
        }
    }
}
