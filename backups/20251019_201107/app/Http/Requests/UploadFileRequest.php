<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        $config = config('security.uploads');
        $maxSizeKb = (int) (($config['max_size'] ?? 10240)); // already in KB per config
        $allowed = $config['allowed_extensions'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip'];

        return [
            'file' => ['required', 'file', 'max:'.$maxSizeKb, 'mimes:'.implode(',', $allowed)],
            'path' => ['nullable', 'string'],
        ];
    }

    public function sanitizeFilename(string $original, string $extension): string
    {
        $base = pathinfo($original, PATHINFO_FILENAME);
        $sanitized = str($base)->slug('-')->toString();
        $uuid = bin2hex(random_bytes(16));
        $ext = strtolower($extension);

        return $sanitized.'-'.$uuid.'.'.$ext;
    }
}
