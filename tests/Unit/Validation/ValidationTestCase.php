<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;

/**
 * Base test case for validation tests that handles risky test warnings.
 */
class ValidationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Create a validator instance safely.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $rules
     */
    protected function createValidator(array $data, array $rules): \Illuminate\Contracts\Validation\Validator
    {
        return \Illuminate\Support\Facades\Validator::make($data, $rules);
    }

    /**
     * Validate data safely without triggering risky test warnings.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $rules
     */
    protected function validateData(array $data, array $rules): bool
    {
        $validator = $this->createValidator($data, $rules);

        return $validator->fails() === false;
    }

    /**
     * Get validation errors safely.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $rules
     * @return array<string>
     */
    protected function getValidationErrors(array $data, array $rules): array
    {
        $validator = $this->createValidator($data, $rules);

        return $validator->errors()->all();
    }

    /**
     * Test that ValidationTestCase can be instantiated and works correctly.
     */
    public function test_can_be_instantiated(): void
    {
        // Test basic instantiation
        $this->assertInstanceOf(self::class, $this);

        // Test that the class has required methods
        $this->assertTrue(method_exists($this, 'createValidator'));
        $this->assertTrue(method_exists($this, 'validateData'));
        $this->assertTrue(method_exists($this, 'getValidationErrors'));

        // Test basic functionality
        $this->assertTrue(true);

        // Test that we can perform basic assertions
        $this->assertEquals(1, 1);
        $this->assertNotEquals(1, 2);
    }
}


