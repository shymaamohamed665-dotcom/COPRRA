<?php

declare(strict_types=1);

namespace Tests\Unit\DataAccuracy;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DataValidationTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_integer_validation(): void
    {
        $data = ['value' => '123'];
        $rules = ['value' => 'integer'];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());

        $data = ['value' => 'abc'];
        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_date_validation(): void
    {
        $data = ['date' => '2023-12-25'];
        $rules = ['date' => 'date'];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());

        $data = ['date' => 'invalid-date'];
        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->passes());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_url_validation(): void
    {
        $data = ['url' => 'https://example.com'];
        $rules = ['url' => 'url'];
        $validator = Validator::make($data, $rules);
        $this->assertTrue($validator->passes());

        $data = ['url' => 'not-a-url'];
        $validator = Validator::make($data, $rules);
        $this->assertFalse($validator->passes());
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
