<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Reward;
use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PointsService
{
    public function addPoints(
        User $user,
        int $points,
        string $type,
        string $source,
        ?int $orderId = null,
        ?string $description = null
    ): UserPoint {
        if ($points === 0) {
            throw new \InvalidArgumentException('Points cannot be zero.');
        }

        return UserPoint::create([
            'user_id' => $user->id,
            'points' => $points,
            'type' => $type,
            'source' => $source,
            'order_id' => $orderId,
            'description' => $description,
            'expires_at' => $this->calculateExpirationDate($type),
        ]);
    }

    public function redeemPoints(User $user, int $points, ?string $description = null): bool
    {
        $availablePoints = $this->getAvailablePoints($user->id);

        if ($availablePoints < $points) {
            return false;
        }

        DB::transaction(function () use ($user, $points, $description): void {
            $this->addPoints($user, -$points, 'redeemed', 'manual_redemption', null, $description);
        });

        return true;
    }

    public function getAvailablePoints(int $userId): int
    {
        $sum = UserPoint::where('user_id', $userId)
            ->valid()
            ->sum('points');

        return is_numeric($sum) ? (int) $sum : 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, UserPoint>
     */
    public function getPointsHistory(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return UserPoint::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function awardPurchasePoints(Order $order): void
    {
        $points = (int) ($order->total_amount * 0.01); // 1 point per dollar

        $user = $order->user;
        if (! $user) {
            return;
        }

        $this->addPoints(
            $user,
            $points,
            'earned',
            'purchase',
            $order->id,
            "Points earned for order #{$order->order_number}"
        );
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function getAvailableRewards(int $userId): array
    {
        $availablePoints = $this->getAvailablePoints($userId);

        return Reward::availableForPoints($availablePoints)
            ->orderBy('points_required')
            ->get()
            ->toArray();
    }

    public function redeemReward(User $user, int $rewardId): bool
    {
        $reward = Reward::findOrFail($rewardId);
        $availablePoints = $this->getAvailablePoints($user->id);

        if ($availablePoints < $reward->points_required) {
            return false;
        }

        return DB::transaction(function () use ($user, $reward) {
            $this->redeemPoints($user, $reward->points_required, "Redeemed reward: {$reward->name}");

            // Apply reward benefits
            $this->applyReward($user, $reward);

            return true;
        });
    }

    private function applyReward(User $user, Reward $reward): void
    {
        switch ($reward->type) {
            case 'discount':
                Log::info("Applying discount reward for user {$user->id}: {$reward->name}");
                // Store discount in user session or create discount code
                break;
            case 'free_shipping':
                Log::info("Applying free shipping reward for user {$user->id}: {$reward->name}");
                // Apply free shipping flag
                break;
            case 'gift':
                Log::info("Applying gift reward for user {$user->id}: {$reward->name}");
                // Add gift to cart or send notification
                break;
            case 'cashback':
                Log::info("Applying cashback reward for user {$user->id}: {$reward->name}");
                // Add cashback to user account
                break;
        }
    }

    private function calculateExpirationDate(string $type): ?\DateTime
    {
        if ($type === 'earned') {
            return now()->addYear(); // Points expire after 1 year
        }

        return null; // Redeemed points don't expire
    }
}
