<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SQLiteConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_connects_to_sqlite_memory_in_testing_environment(): void
    {
        $default = (string) config('database.default');
        $this->assertSame('sqlite', $default, 'بيئة الاختبار يجب أن تستخدم sqlite');

        // Ensure connection works and can run a basic query
        $result = DB::select('select 1 as one');
        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result[0]->one ?? null);
    }
}
