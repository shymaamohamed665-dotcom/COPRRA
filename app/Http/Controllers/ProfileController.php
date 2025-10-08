<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Update the user profile.
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return back()->withErrors(['error' => 'User not authenticated.']);
        }

        $this->validateRequest($request, $user);
        $this->updateProfileData($request, $user);

        if ($request->filled('new_password')) {
            $passwordUpdateResult = $this->updatePassword($request, $user);
            if ($passwordUpdateResult) {
                return $passwordUpdateResult;
            }
        }

        $user->save();

        return back()->with('status', 'Profile updated successfully!');
    }

    /**
     * Validate the request data.
     */
    private function validateRequest(Request $request, User $user): void
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);
    }

    /**
     * Update the user's profile data.
     */
    private function updateProfileData(Request $request, User $user): void
    {
        $name = $request->input('name');
        if (is_string($name)) {
            $user->name = $name;
        }

        $email = $request->input('email');
        if (is_string($email)) {
            $user->email = $email;
        }
    }

    /**
     * Update the user's password.
     */
    private function updatePassword(Request $request, User $user): ?RedirectResponse
    {
        $currentPassword = $request->input('current_password');
        $newPassword = $request->input('new_password');

        if (! is_string($currentPassword) || ! Hash::check($currentPassword, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        if (is_string($newPassword)) {
            $user->password = Hash::make($newPassword);
        }

        return null;
    }
}
