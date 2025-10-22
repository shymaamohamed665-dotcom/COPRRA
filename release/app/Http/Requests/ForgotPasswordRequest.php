<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * Get custom messages for validator errors.
     *
     * @return array<string>
     *
     * @psalm-return array{'email.required': 'The email field is required.', 'email.email': 'The email must be a valid email address.', 'email.exists': 'We could not find a user with that email address.'}
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.exists' => 'We could not find a user with that email address.',
        ];
    }
}
