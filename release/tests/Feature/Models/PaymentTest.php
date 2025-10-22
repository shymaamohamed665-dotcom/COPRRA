<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PaymentTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_payment(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $attributes = [
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
            'transaction_id' => 'TXN-12345',
            'status' => 'completed',
            'amount' => 100.00,
            'currency' => 'USD',
            'gateway_response' => ['status' => 'success', 'reference' => 'ref123'],
            'processed_at' => now(),
        ];

        // Act
        $payment = Payment::create($attributes);

        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('TXN-12345', $payment->transaction_id);
        $this->assertEquals('completed', $payment->status);
        $this->assertEquals(100.00, $payment->amount);
        $this->assertEquals('USD', $payment->currency);
        $this->assertIsArray($payment->gateway_response);
        $this->assertInstanceOf(\Carbon\Carbon::class, $payment->processed_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_relationships(): void
    {
        // Arrange
        $order = Order::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        // Act
        $payment->refresh();

        // Assert
        $this->assertInstanceOf(Order::class, $payment->order);
        $this->assertEquals($order->id, $payment->order->id);
        $this->assertInstanceOf(PaymentMethod::class, $payment->paymentMethod);
        $this->assertEquals($paymentMethod->id, $payment->paymentMethod->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_casts_attributes_correctly(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'gateway_response' => ['code' => 200, 'message' => 'OK'],
            'processed_at' => '2023-01-01 12:00:00',
        ]);

        // Act & Assert
        $this->assertIsArray($payment->gateway_response);
        $this->assertEquals(200, $payment->gateway_response['code']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $payment->processed_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_by_status(): void
    {
        // Arrange
        Payment::factory()->create(['status' => 'pending']);
        Payment::factory()->create(['status' => 'completed']);
        Payment::factory()->create(['status' => 'failed']);
        Payment::factory()->create(['status' => 'pending']);

        // Act
        $pendingPayments = Payment::byStatus('pending')->get();
        $completedPayments = Payment::byStatus('completed')->get();

        // Assert
        $this->assertCount(2, $pendingPayments);
        $this->assertCount(1, $completedPayments);
        $pendingPayments->each(function ($payment) {
            $this->assertEquals('pending', $payment->status);
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_fillable_attributes(): void
    {
        // Arrange
        $fillable = [
            'order_id',
            'payment_method_id',
            'transaction_id',
            'status',
            'amount',
            'currency',
            'gateway_response',
            'processed_at',
        ];

        // Act
        $payment = new Payment;

        // Assert
        $this->assertEquals($fillable, $payment->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_status_transitions(): void
    {
        // Arrange
        $payment = Payment::factory()->create(['status' => 'pending']);

        // Act
        $payment->update(['status' => 'processing']);
        $payment->update(['status' => 'completed', 'processed_at' => now()]);

        // Assert
        $this->assertEquals('completed', $payment->status);
        $this->assertNotNull($payment->processed_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_amount_and_currency(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'amount' => 250.75,
            'currency' => 'EUR',
        ]);

        // Act & Assert
        $this->assertEquals(250.75, $payment->amount);
        $this->assertEquals('EUR', $payment->currency);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_payment_gateway_response_storage(): void
    {
        // Arrange
        $response = [
            'transaction_id' => 'ext-123',
            'authorization_code' => 'auth-456',
            'response_code' => '00',
            'response_message' => 'Approved',
        ];

        // Act
        $payment = Payment::factory()->create(['gateway_response' => $response]);

        // Assert
        $this->assertEquals($response, $payment->gateway_response);
        $this->assertEquals('ext-123', $payment->gateway_response['transaction_id']);
    }
}
