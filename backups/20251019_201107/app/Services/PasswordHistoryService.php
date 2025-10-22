<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class PasswordHistoryService
{
    /**
     * @var array<string, int>
     */
    private array $config = [];

    /**
     * التحقق من وجود كلمة المرور في التاريخ.
     */
    public function isPasswordInHistory(string $password, int $userId): bool
    {
        try {
            $history = $this->getPasswordHistory($userId);

            foreach ($history as $oldPassword) {
                if (Hash::check($password, $oldPassword)) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            Log::error('Password history check failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * حفظ كلمة المرور في التاريخ.
     */
    public function savePasswordToHistory(string $password, int $userId): void
    {
        try {
            $hashedPassword = Hash::make($password);
            $history = $this->getPasswordHistory($userId);

            // إضافة كلمة المرور الجديدة
            array_unshift($history, $hashedPassword);

            // الحفاظ على العدد المحدد من كلمات المرور
            $historyCount = is_numeric($this->config['history_count']) ? (int) $this->config['history_count'] : 5;
            $history = array_slice($history, 0, $historyCount);

            // حفظ التاريخ
            Cache::put("password_history_{$userId}", $history, 86400 * 30); // 30 يوم

            Log::info("Password saved to history for user {$userId}");
        } catch (Exception $e) {
            Log::error('Failed to save password to history: '.$e->getMessage());
        }
    }

    /**
     * مسح تاريخ كلمات المرور للمستخدم.
     */
    public function clearPasswordHistory(int $userId): void
    {
        try {
            Cache::forget("password_history_{$userId}");
            Log::info("Password history cleared for user {$userId}");
        } catch (Exception $e) {
            Log::error('Failed to clear password history: '.$e->getMessage());
        }
    }

    /**
     * الحصول على تاريخ كلمات المرور.
     *
     * @return array<string>
     *
     * @psalm-return list<string>
     */
    private function getPasswordHistory(int $userId): array
    {
        $history = Cache::get("password_history_{$userId}", []);

        // تأكيد أن كل عنصر نصي فقط
        if (! is_array($history)) {
            return [];
        }

        return array_values(array_filter($history, 'is_string'));
    }
}
