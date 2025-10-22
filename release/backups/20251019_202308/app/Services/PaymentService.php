<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal;
use Stripe\StripeClient;

final readonly class PaymentService
{
    private StripeClient $stripe;

    private PayPal $paypal;

    public function __construct(StripeClient $stripe, PayPal $paypal)
    {
        $this->stripe = $stripe;
        $this->paypal = $paypal;
    }

    /**
     * @param  array<string, string>  $paymentData
     */
    public function processPayment(Order $order, string $paymentMethodId, array $paymentData): Payment
    {
        $paymentMethod = PaymentMethod::findOrFail($paymentMethodId);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethodId,
            'transaction_id' => $this->generateTransactionId(),
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => 'processing',
        ]);

        try {
            switch ($paymentMethod->gateway) {
                case 'stripe':
                    $result = $this->processStripePayment($payment, $paymentData);

                    break;
                case 'paypal':
                    $result = $this->processPayPalPayment($payment);

                    break;
                default:
                    throw new \Exception('Unsupported payment gateway');
            }

            $payment->update([
                'status' => $result['status'],
                'gateway_response' => $result['response'],
                'processed_at' => now(),
            ]);

            if ($result['status'] === 'completed') {
                $order->update(['status' => 'processing']);
            }
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->update([
                'status' => 'failed',
                'gateway_response' => ['error' => $e->getMessage()],
            ]);
        }

        return $payment;
    }

    public function refundPayment(Payment $payment, ?float $amount = null): bool
    {
        $refundAmount = $amount ?? $payment->amount;

        try {
            if (! $payment->paymentMethod) {
                throw new \Exception('Payment method not found for this payment.');
            }

            switch ($payment->paymentMethod->gateway) {
                case 'stripe':
                    $gatewayResponse = $payment->gateway_response;
                    $paymentIntentId = is_array($gatewayResponse) && is_string($gatewayResponse['id'] ?? null) ? $gatewayResponse['id'] : '';
                    if ($paymentIntentId !== '' && $paymentIntentId !== '0') {
                        $this->stripe->refunds->create([
                            'payment_intent' => $paymentIntentId,
                            'amount' => (int) round($refundAmount * 100),
                        ]);
                    }

                    break;
                case 'paypal':
                    // PayPal refund would be implemented here
                    // $this->paypal->refundTransaction($payment->transaction_id, $refundAmount);
                    break;
            }

            $payment->update(['status' => 'refunded']);

            return true;
        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, string>  $data
     *
     * @return array<string, array<string, int|string|list<string>>|string>
     */
    private function processStripePayment(Payment $payment, array $data): array
    {
        $paymentMethodId = $data['payment_method_id'] ?? '';
        $intent = $this->stripe->paymentIntents->create([
            'amount' => (int) ((is_numeric($payment->amount) ? $payment->amount : 0) * 100), // Convert to cents and cast to int
            'currency' => $payment->currency,
            'payment_method' => is_string($paymentMethodId) ? $paymentMethodId : '',
            'confirmation_method' => 'manual',
            'confirm' => true,
        ]);

        return [
            'status' => $intent->status === 'succeeded' ? 'completed' : 'failed',
            'response' => $intent->toArray(),
        ];
    }

    /**
     * @return array{status: string, response: array<string, string|int|float|array<string, array<string, string|int|float|null>>|bool|null>}
     */
    private function processPayPalPayment(Payment $payment): array
    {
        $response = $this->paypal->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $payment->currency,
                        'value' => $payment->amount,
                    ],
                ],
            ],
        ]);

        $responseStatus = is_array($response) ? ($response['status'] ?? '') : '';

        return [
            'status' => $responseStatus === 'COMPLETED' ? 'completed' : 'failed',
            'response' => is_array($response) ? $response : [],
        ];
    }

    private function generateTransactionId(): string
    {
        return 'TXN_'.time().'_'.strtoupper(substr(md5(uniqid()), 0, 8));
    }
}
