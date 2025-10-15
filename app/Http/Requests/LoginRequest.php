<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @property string $email
     * @property string $password
     * @property bool $remember
     */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{email: 'required|email|max:255', password: 'required|string|min:8', remember: 'boolean'}
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
            'remember' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return string[]
     *
     * @psalm-return array{'email.required': 'The email field is required.', 'email.email': 'The email must be a valid email address.', 'email.max': 'The email may not be greater than 255 characters.', 'password.required': 'The password field is required.', 'password.string': 'The password must be a string.', 'password.min': 'The password must be at least 8 characters.'}
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.max' => 'The email may not be greater than 255 characters.',
            'password.required' => 'The password field is required.',
            'password.string' => 'The password must be a string.',
            'password.min' => 'The password must be at least 8 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return string[]
     *
     * @psalm-return array{email: 'email address', password: 'password', remember: 'remember me'}
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'password' => 'password',
            'remember' => 'remember me',
        ];
    }

    /**
     * Attempt to authenticate the user.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        if (! auth()->attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
    }
}
