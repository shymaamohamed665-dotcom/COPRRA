<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    /** @phpstan-ignore-next-line */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'gateway',
        'type',
        'config',
        'is_active',
        'is_default',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Return only explicit casts defined on the model.
     * This excludes framework-added defaults like the primary key cast.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Payment, PaymentMethod>
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // --- Scopes ---

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<PaymentMethod>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<PaymentMethod>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeDefault(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_default', true);
    }
}
