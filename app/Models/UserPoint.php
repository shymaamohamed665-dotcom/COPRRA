<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPoint extends Model
{
    /** @phpstan-ignore-next-line */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'points',
        'type',
        'source',
        'order_id',
        'description',
        'expires_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<UserPoint>  $query
     * @return \Illuminate\Database\Eloquent\Builder<UserPoint>
     */
    public function scopeValid(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('expires_at', '>', now())->orWhereNull('expires_at');
    }
}
