<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class CreatesApplicationTest extends TestCase
{
    public function test_application_can_be_created(): void
    {
        $this->assertNotNull($this->app);
        $this->assertInstanceOf(\Illuminate\Foundation\Application::class, $this->app);
    }
}
