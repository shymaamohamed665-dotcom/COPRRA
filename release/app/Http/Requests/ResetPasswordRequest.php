<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Get custom messages for validator errors.
     *
     * @return array<string>
     *
     * @psalm-return array{'token.required': 'The reset token is required.', 'email.required': 'The email field is required.', 'email.email': 'The email must be a valid email address.', 'email.exists': 'We could not find a user with that email address.', 'password.required': 'The password field is required.', 'password.confirmed': 'The password confirmation does not match.'}
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'token.required' => 'The reset token is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.exists' => 'We could not find a user with that email address.',
            'password.required' => 'The password field is required.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
