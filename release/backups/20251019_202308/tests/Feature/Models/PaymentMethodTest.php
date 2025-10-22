<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_a_payment_method(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'name' => 'Credit Card',
            'gateway' => 'stripe',
            'type' => 'card',
            'config' => ['key' => 'value'],
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals('Credit Card', $paymentMethod->name);
        $this->assertEquals('stripe', $paymentMethod->gateway);
        $this->assertEquals('card', $paymentMethod->type);
        $this->assertIsArray($paymentMethod->config);
        $this->assertTrue($paymentMethod->is_active);
        $this->assertFalse($paymentMethod->is_default);

        $this->assertDatabaseHas('payment_methods', [
            'name' => 'Credit Card',
            'gateway' => 'stripe',
            'type' => 'card',
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_casts_attributes_correctly(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'config' => ['api_key' => 'secret'],
            'is_active' => 1,
            'is_default' => 0,
        ]);

        $this->assertIsArray($paymentMethod->config);
        $this->assertEquals(['api_key' => 'secret'], $paymentMethod->config);
        $this->assertIsBool($paymentMethod->is_active);
        $this->assertIsBool($paymentMethod->is_default);
        $this->assertTrue($paymentMethod->is_active);
        $this->assertFalse($paymentMethod->is_default);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_many_payments(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $payment1 = Payment::factory()->create(['payment_method_id' => $paymentMethod->id]);
        $payment2 = Payment::factory()->create(['payment_method_id' => $paymentMethod->id]);

        $this->assertCount(2, $paymentMethod->payments);
        $this->assertTrue($paymentMethod->payments->contains($payment1));
        $this->assertTrue($paymentMethod->payments->contains($payment2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_active_scope(): void
    {
        PaymentMethod::factory()->create(['is_active' => true]);
        PaymentMethod::factory()->create(['is_active' => false]);

        $activeMethods = PaymentMethod::active()->get();

        $this->assertCount(1, $activeMethods);
        $this->assertTrue($activeMethods->first()->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_default_scope(): void
    {
        PaymentMethod::factory()->create(['is_default' => true]);
        PaymentMethod::factory()->create(['is_default' => false]);

        $defaultMethods = PaymentMethod::default()->get();

        $this->assertCount(1, $defaultMethods);
        $this->assertTrue($defaultMethods->first()->is_default);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_fillable_attributes(): void
    {
        $fillable = [
            'name',
            'gateway',
            'type',
            'config',
            'is_active',
            'is_default',
        ];

        $this->assertEquals($fillable, (new PaymentMethod)->getFillable());
    }
}
