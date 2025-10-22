<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'payment_method_id',
        'transaction_id',
        'status',
        'amount',
        'currency',
        'gateway_response',
        'processed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Order, Payment>
     */
    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<PaymentMethod, Payment>
     */
    public function paymentMethod(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // --- Scopes ---

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Payment>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeByStatus(\Illuminate\Database\Eloquent\Builder $query, string $status): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', $status);
    }
}
