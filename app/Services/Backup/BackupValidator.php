<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BackupValidator
{
    /**
     * Validate backup creation request.
     *
     * @return array<string, string>
     *
     * @throws ValidationException
     */
    public function validateBackupRequest(Request $request): array
    {
        $validated = $request->validate([
            'type' => 'required|in:full,database,files',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:500',
        ]);

        return [
            'type' => $validated['type'],
            'name' => $validated['name'] ?? 'backup_'.now()->format('Y-m-d_H-i-s'),
            'description' => $validated['description'] ?? '',
        ];
    }

    /**
     * Validate backup type.
     */
    public function validateBackupType(string $type): bool
    {
        return in_array($type, ['full', 'database', 'files'], true);
    }

    /**
     * Validate backup name.
     */
    public function validateBackupName(string $name): bool
    {
        return $name !== '' && strlen($name) <= 255;
    }

    /**
     * Validate backup description.
     */
    public function validateBackupDescription(string $description): bool
    {
        return strlen($description) <= 500;
    }
}
