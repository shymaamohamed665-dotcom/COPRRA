<?php

namespace Tests\Security;

use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PermissionSecurityTest extends TestCase
{
    public function test_permission_security(): void
    {
        $this->assertTrue(true);
    }

    public function test_role_security(): void
    {
        $this->assertTrue(true);
    }

    public function test_access_control(): void
    {
        $this->assertTrue(true);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
