<?php

declare(strict_types=1);

namespace App\Services;

final class PasswordPolicyService
{
    /**
     * @var array<string, bool|int|array<int, string>>
     */
    private array $config;

    private PasswordHistoryService $passwordHistoryService;

    /**
     * @param  array<string, bool|int|array<int, string>>  $config
     */
    public function __construct(array $config = [], ?PasswordHistoryService $passwordHistoryService = null)
    {
        $this->config = $this->loadConfig($config);
        $this->passwordHistoryService = $passwordHistoryService ?? new PasswordHistoryService;
    }

    /**
     * @return array{valid: bool, errors: array<int, string>, strength: array{score: int, feedback: string}}
     */
    public function validatePassword(string $password, ?int $userId = null): array
    {
        $errors = array_merge(
            $this->validateLength($password),
            $this->validateCharacterTypes($password),
            $this->validateForbiddenPasswords($password),
            $this->validatePasswordHistory($password, $userId),
            $this->checkCommonPatterns($password)
        );

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'strength' => $this->calculatePasswordStrength($password),
        ];
    }

    public function savePasswordToHistory(int $userId, string $password): void
    {
        $this->passwordHistoryService->savePasswordToHistory($password, $userId);
    }

    /**
     * @param  array<string, bool|int|array<int, string>>  $config
     * @return array<string, bool|int|array<int, string>>
     */
    private function loadConfig(array $config): array
    {
        $defaults = [
            'min_length' => 8,
            'max_length' => 128,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'forbidden_passwords' => [],
        ];

        return array_merge($defaults, $config);
    }

    /**
     * @return array<int, string>
     */
    private function validateLength(string $password): array
    {
        $errors = [];
        $minLength = (int) $this->config['min_length'];
        $maxLength = (int) $this->config['max_length'];

        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long";
        }

        if (strlen($password) > $maxLength) {
            $errors[] = "Password must not exceed {$maxLength} characters";
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    private function validateCharacterTypes(string $password): array
    {
        $errors = [];
        $validations = [
            'require_uppercase' => ['/[A-Z]/', 'Password must contain at least one uppercase letter'],
            'require_lowercase' => ['/[a-z]/', 'Password must contain at least one lowercase letter'],
            'require_numbers' => ['/\d/', 'Password must contain at least one number'],
            'require_symbols' => ['/[^A-Za-z0-9]/', 'Password must contain at least one special character'],
        ];

        foreach ($validations as $configKey => [$pattern, $message]) {
            if ($this->config[$configKey] && ! preg_match($pattern, $password)) {
                $errors[] = $message;
            }
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    private function validateForbiddenPasswords(string $password): array
    {
        $forbiddenPasswords = $this->config['forbidden_passwords'] ?? [];
        if (! is_array($forbiddenPasswords)) {
            return [];
        }

        $lowercasePassword = strtolower($password);
        foreach ($forbiddenPasswords as $forbidden) {
            if (is_string($forbidden) && str_contains($lowercasePassword, strtolower($forbidden))) {
                return ['Password is too common and not allowed'];
            }
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function validatePasswordHistory(string $password, ?int $userId): array
    {
        if ($userId && $this->passwordHistoryService->isPasswordInHistory($password, $userId)) {
            return ['Password has been used recently and is not allowed'];
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function checkCommonPatterns(string $password): array
    {
        return array_merge(
            $this->checkSequentialCharacters($password),
            $this->checkKeyboardPatterns($password),
            $this->checkCommonSubstitutions($password)
        );
    }

    /**
     * @return array<int, string>
     */
    private function checkSequentialCharacters(string $password): array
    {
        return preg_match('/(.)\1{2,}/', $password) ? ['Password contains repeated characters'] : [];
    }

    /**
     * @return array<int, string>
     */
    private function checkKeyboardPatterns(string $password): array
    {
        $patterns = ['qwerty', 'asdf', 'zxcv', '1234', 'abcd'];
        foreach ($patterns as $pattern) {
            if (stripos($password, $pattern) !== false) {
                return ['Password contains keyboard patterns'];
            }
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private function checkCommonSubstitutions(string $password): array
    {
        $substitutions = [
            'password' => ['p@ssw0rd', 'p@ssword', 'passw0rd'],
            'admin' => ['@dmin', 'adm1n', '@dm1n'],
        ];

        foreach ($substitutions as $words) {
            foreach ($words as $word) {
                if (stripos($password, $word) !== false) {
                    return ['Password contains common character substitutions'];
                }
            }
        }

        return [];
    }

    private function calculatePasswordStrength(string $password): string
    {
        $score = $this->calculateLengthScore($password) +
            $this->calculateVarietyScore($password) +
            $this->calculateComplexityScore($password);

        return $this->determineStrengthLevel($score);
    }

    private function calculateLengthScore(string $password): int
    {
        $length = strlen($password);
        if ($length >= 16) {
            return 3;
        }
        if ($length >= 12) {
            return 2;
        }
        if ($length >= 8) {
            return 1;
        }

        return 0;
    }

    private function calculateVarietyScore(string $password): int
    {
        $score = 0;
        if (preg_match('/[a-z]/', $password)) {
            $score++;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $score++;
        }
        if (preg_match('/\d/', $password)) {
            $score++;
        }
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score++;
        }

        return $score;
    }

    private function calculateComplexityScore(string $password): int
    {
        $length = strlen($password);
        if ($length === 0) {
            return 0;
        }

        $uniqueChars = count(array_unique(str_split($password)));

        return $uniqueChars / $length > 0.7 ? 1 : 0;
    }

    private function determineStrengthLevel(int $score): string
    {
        if ($score >= 7) {
            return 'very_strong';
        }
        if ($score >= 6) {
            return 'strong';
        }
        if ($score >= 4) {
            return 'medium';
        }

        return 'weak';
    }
}
