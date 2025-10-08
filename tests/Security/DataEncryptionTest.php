<?php

namespace Tests\Security;

use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DataEncryptionTest extends TestCase
{
    public function test_data_encryption(): void
    {
        $data = 'test data';
        $encrypted = Crypt::encrypt($data);
        $this->assertNotEquals($data, $encrypted);
        $this->assertEquals($data, Crypt::decrypt($encrypted));
    }

    public function test_encryption_keys(): void
    {
        $data = 'secret';
        $encrypted1 = Crypt::encrypt($data);
        $encrypted2 = Crypt::encrypt($data);
        $this->assertNotEquals($encrypted1, $encrypted2); // Different each time
        $this->assertEquals($data, Crypt::decrypt($encrypted1));
        $this->assertEquals($data, Crypt::decrypt($encrypted2));
    }

    public function test_decryption_works(): void
    {
        $data = 'another test';
        $encrypted = Crypt::encrypt($data);
        $decrypted = Crypt::decrypt($encrypted);
        $this->assertEquals($data, $decrypted);
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
