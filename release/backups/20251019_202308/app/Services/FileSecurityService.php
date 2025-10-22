<?php

declare(strict_types=1);

namespace App\Services;

final class FileSecurityService
{
    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
        'pdf',
        'doc',
        'docx',
        'txt',
        'rtf',
        'xls',
        'xlsx',
        'csv',
        'zip',
        'rar',
        '7z',
    ];

    private const DANGEROUS_EXTENSIONS = [
        'exe',
        'bat',
        'cmd',
        'com',
        'pif',
        'scr',
        'vbs',
        'js',
        'jar',
        'php',
        'asp',
        'aspx',
        'jsp',
        'py',
        'rb',
        'pl',
        'sh',
        'ps1',
        'psm1',
        'psd1',
        'ps1xml',
        'psc1',
        'psc2',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    /**
     * Get file security statistics.
     */
    /**
     * @return array<int|array<string>>
     *
     * @psalm-return array{allowed_extensions: list{'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'txt', 'rtf', 'xls', 'xlsx', 'csv', 'zip', 'rar', '7z'}, dangerous_extensions: list{'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp', 'aspx', 'jsp', 'py', 'rb', 'pl', 'sh', 'ps1', 'psm1', 'psd1', 'ps1xml', 'psc1', 'psc2'}, max_file_size: 10485760, max_file_size_mb: 10}
     */
    public function getStatistics(): array
    {
        return [
            'allowed_extensions' => self::ALLOWED_EXTENSIONS,
            'dangerous_extensions' => self::DANGEROUS_EXTENSIONS,
            'max_file_size' => self::MAX_FILE_SIZE,
            'max_file_size_mb' => self::MAX_FILE_SIZE / (1024 * 1024),
        ];
    }
}
