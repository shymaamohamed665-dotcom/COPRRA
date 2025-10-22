<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class PasswordValidator
{
    /**
     * @var array{
     *   min_length: int,
     *   require_uppercase: bool,
     *   require_lowercase: bool,
     *   require_numbers: bool,
     *   require_symbols: bool,
     *   forbidden_patterns: array<int, string>,
     *   history_count: int
     * }
     */
    private array $config;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->config = $this->loadConfig($configRepository);
    }

    /**
     * Validate the password.
     *
     * @return array<bool|int|array<string>>
     *
     * @psalm-return array{valid: bool, errors: array<int, string>, strength: int<min, 10>}
     */
    public function validatePassword(string $password): array
    {
        $errors = [];

        $this->validateLength($password, $errors);
        $this->validateUppercase($password, $errors);
        $this->validateLowercase($password, $errors);
        $this->validateNumbers($password, $errors);
        $this->validateSymbols($password, $errors);
        $this->validateForbiddenPatterns($password, $errors);

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'strength' => $this->calculatePasswordStrength($password),
        ];
    }

    /**
     * @return array<array|int|mixed|true>
     *
     * @psalm-return array{min_length: 8|mixed, require_uppercase: mixed|true, require_lowercase: mixed|true, require_numbers: mixed|true, require_symbols: mixed|true, forbidden_patterns: array<never, never>|mixed, history_count: 5|mixed,...}
     */
    private function loadConfig(ConfigRepository $configRepository): array
    {
        $defaultConfig = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'forbidden_patterns' => [],
            'history_count' => 5,
        ];

        $configValue = $configRepository->get('password_policy', $defaultConfig);

        return is_array($configValue) ? array_merge($defaultConfig, $configValue) : $defaultConfig;
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function validateLength(string $password, array &$errors): void
    {
        $minLength = (int) $this->config['min_length'];
        if (strlen($password) < $minLength) {
            $errors[] = "كلمة المرور يجب أن تكون على الأقل {$minLength} أحرف";
        }
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function validateUppercase(string $password, array &$errors): void
    {
        if ($this->config['require_uppercase'] && ! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على حرف كبير واحد على الأقل';
        }
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function validateLowercase(string $password, array &$errors): void
    {
        if ($this->config['require_lowercase'] && ! preg_match('/[a-z]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على حرف صغير واحد على الأقل';
        }
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function validateNumbers(string $password, array &$errors): void
    {
        if ($this->config['require_numbers'] && ! preg_match('/\d/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل';
        }
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function validateSymbols(string $password, array &$errors): void
    {
        if ($this->config['require_symbols'] && ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'كلمة المرور يجب أن تحتوي على رمز خاص واحد على الأقل';
        }
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function validateForbiddenPatterns(string $password, array &$errors): void
    {
        $forbiddenPatterns = $this->config['forbidden_patterns'] ?? [];
        if (! is_array($forbiddenPatterns)) {
            return;
        }

        foreach ($forbiddenPatterns as $pattern) {
            if (is_string($pattern) && preg_match($pattern, $password)) {
                $errors[] = 'كلمة المرور تحتوي على نمط محظور';

                break;
            }
        }
    }

    /**
     * Calculate password strength.
     *
     * @psalm-return int<min, 10>
     */
    private function calculatePasswordStrength(string $password): int
    {
        $score = min((int) (strlen($password) / 4), 3);
        $diversity = $this->calculateDiversity($password);

        if ($diversity >= 3) {
            $score += $diversity;
        }

        return min($score, 10);
    }

    /**
     * @psalm-return int<0, max>
     */
    private function calculateDiversity(string $password): int
    {
        $patterns = [
            '/[a-z]/',
            '/[A-Z]/',
            '/\d/',
            '/[^A-Za-z0-9]/',
        ];

        $diversity = 0;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $password)) {
                $diversity++;
            }
        }

        return $diversity;
    }
}
