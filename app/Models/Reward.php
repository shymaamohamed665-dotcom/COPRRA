<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @phpstan-type TFactory \Illuminate\Database\Eloquent\Factories\Factory<Reward>
 */
class Reward extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'points_required',
        'type',
        'value',
        'is_active',
        'usage_limit',
        'valid_from',
        'valid_until',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Reward>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Reward>
     */
    public function scopeAvailableForPoints(\Illuminate\Database\Eloquent\Builder $query, int $points): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true)
            ->where('points_required', '<=', $points)
            ->where(function ($q): void {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            });
    }
}
