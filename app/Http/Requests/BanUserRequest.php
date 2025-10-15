<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BanUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{reason: 'required|string|max:500', duration_hours: 'sometimes|integer|min:1|max:8760', notify_user: 'sometimes|boolean'}
     */
    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:500',
            'duration_hours' => 'sometimes|integer|min:1|max:8760', // Max 1 year
            'notify_user' => 'sometimes|boolean',
        ];
    }
}
