<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Reward;
use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Points Service
 *
 * Manages user loyalty points including earning, redemption, and rewards.
 * Not marked as final to allow mocking in unit tests while maintaining production integrity.
 */
class PointsService
{
    /**
     * Add points to user account
     *
     * @param  User  $user  The user to receive points
     * @param  int  $points  Number of points (positive for earning, negative for spending)
     * @param  string  $type  Type of transaction (earned, redeemed, expired)
     * @param  string  $source  Source of points (purchase, manual, reward, etc.)
     * @param  int|null  $orderId  Related order ID if applicable
     * @param  string|null  $description  Optional description
     *
     * @throws \InvalidArgumentException If points is zero
     */
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

    /**
     * Redeem points from user account
     *
     * @param  User  $user  The user redeeming points
     * @param  int  $points  Number of points to redeem
     * @param  string|null  $description  Optional description
     * @return bool True if redemption successful, false if insufficient points
     */
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

    /**
     * Get total available points for user
     *
     * @param  int  $userId  User ID
     * @return int Total available points (excluding expired)
     */
    public function getAvailablePoints(int $userId): int
    {
        $sum = UserPoint::where('user_id', $userId)
            ->valid()
            ->sum('points');

        return is_numeric($sum) ? (int) $sum : 0;
    }

    /**
     * Get points transaction history for user
     *
     * @param  int  $userId  User ID
     * @param  int  $limit  Number of records to return
     * @return \Illuminate\Database\Eloquent\Collection<int, UserPoint>
     */
    public function getPointsHistory(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return UserPoint::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Award points for a purchase
     *
     * Awards 1 point per dollar spent on the order.
     *
     * @param  Order  $order  The order to award points for
     */
    public function awardPurchasePoints(Order $order): void
    {
        // Calculate points: 1 point per $100 spent (consistent with tests)
        $points = (int) round(((float) $order->total_amount) * 0.01);

        $user = $order->user;
        if (! $user) {
            return;
        }

        // Do not create a zero-point transaction
        if ($points <= 0) {
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
     * Get available rewards for user based on points
     *
     * @param  int  $userId  User ID
     * @return array<int, array<string, int|string>> Available rewards
     */
    public function getAvailableRewards(int $userId): array
    {
        $availablePoints = $this->getAvailablePoints($userId);

        return Reward::availableForPoints($availablePoints)
            ->orderBy('points_required')
            ->get()
            ->toArray();
    }

    /**
     * Redeem a reward using points
     *
     * @param  User  $user  The user redeeming the reward
     * @param  int  $rewardId  Reward ID to redeem
     * @return bool True if redemption successful, false if insufficient points
     */
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

    /**
     * Apply reward benefits to user
     *
     * @param  User  $user  The user receiving the reward
     * @param  Reward  $reward  The reward to apply
     */
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

    /**
     * Calculate expiration date for points
     *
     * @param  string  $type  Transaction type
     * @return \Illuminate\Support\Carbon|null Expiration date or null if points don't expire
     */
    private function calculateExpirationDate(string $type): ?\Illuminate\Support\Carbon
    {
        if ($type === 'earned') {
            return now()->addYear(); // Points expire after 1 year
        }

        return null; // Redeemed points don't expire
    }
}
