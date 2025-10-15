<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Get custom messages for validator errors.
     *
     * @return string[]
     *
     * @psalm-return array{'name.required': 'The name field is required.', 'name.string': 'The name must be a string.', 'name.max': 'The name may not be greater than 255 characters.', 'email.required': 'The email field is required.', 'email.email': 'The email must be a valid email address.', 'email.max': 'The email may not be greater than 255 characters.', 'email.unique': 'This email address is already registered.', 'password.required': 'The password field is required.', 'password.string': 'The password must be a string.', 'password.confirmed': 'The password confirmation does not match.'}
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.max' => 'The email may not be greater than 255 characters.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'The password field is required.',
            'password.string' => 'The password must be a string.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return string[]
     *
     * @psalm-return array{name: 'name', email: 'email address', password: 'password'}
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'name',
            'email' => 'email address',
            'password' => 'password',
        ];
    }
}
