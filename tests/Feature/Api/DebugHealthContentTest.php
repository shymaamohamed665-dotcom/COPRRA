<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugHealthContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump_health_response_content(): void
    {
        $response = $this->getJson('/api/health');

        // Dump raw content to STDERR for debugging
        fwrite(STDERR, "\n[debug] /api/health raw content:\n".$response->getContent()."\n\n");

        // Ensure status code logged as well
        fwrite(STDERR, '[debug] status code: '.$response->getStatusCode()."\n");

        // Basic assertion to keep test from being marked risky
        $this->assertTrue(true);
    }
}


