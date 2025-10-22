<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\PasswordPolicyService;
use App\Services\UserBanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        private readonly UserBanService $userBanService,
        private readonly PasswordPolicyService $passwordPolicyService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->applyUserFilters($request, User::with(['wishlists', 'priceAlerts', 'reviews']));

        $users = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Users retrieved successfully',
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['wishlists.product', 'priceAlerts.product', 'reviews.product']);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User retrieved successfully',
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();
        /** @var array<string, string|bool> $validatedData */
        $validatedData = is_array($validated) ? $validated : [];
        $user->update($validatedData);

        return response()->json([
            'success' => true,
            'data' => $user->fresh(),
            'message' => 'User updated successfully',
        ]);
    }

    /**
     * Change user password.
     */
    public function changePassword(ChangePasswordRequest $request, User $user): JsonResponse
    {
        // Verify current password
        $currentPassword = $request->input('current_password');
        if (! Hash::check(is_string($currentPassword) ? $currentPassword : '', $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        // Validate new password against policy
        $newPassword = $request->input('new_password');
        $emptyString = '';
        $passwordValidation = $this->passwordPolicyService->validatePassword(is_string($newPassword) ? $newPassword : $emptyString, $user->id);
        if (! isset($passwordValidation['valid']) || ! $passwordValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Password does not meet policy requirements',
                'errors' => $passwordValidation['errors'],
            ], 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make(is_string($newPassword) ? $newPassword : ''),
        ]);

        // Save password to history
        $passwordValue = $request->input('password');
        $password = is_string($passwordValue) ? $passwordValue : '';
        $this->passwordPolicyService->savePasswordToHistory($user->id, $password);

        return response()->json(['message' => 'Password updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent deletion of admin users
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin users',
            ], 400);
        }

        // Soft delete user and related data
        $user->wishlists()->delete();
        $user->priceAlerts()->delete();
        $user->reviews()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(int $userId): JsonResponse
    {
        User::findOrFail($userId);

        // Note: This would require soft deletes to be implemented in User model
        return response()->json([
            'success' => false,
            'message' => 'Soft deletes not implemented for users',
        ], 501);
    }

    /**
     * Get banned users.
     */
    public function banned(): JsonResponse
    {
        $bannedUsers = $this->userBanService->getBannedUsers();

        return response()->json([
            'success' => true,
            'data' => $bannedUsers,
            'message' => 'Banned users retrieved successfully',
        ]);
    }

    /**
     * Get user's wishlist.
     */
    public function wishlist(User $user): JsonResponse
    {
        $wishlist = $user->wishlists()->with('product.category', 'product.brand')->get();

        return response()->json([
            'success' => true,
            'data' => $wishlist,
            'message' => 'User wishlist retrieved successfully',
        ]);
    }

    /**
     * Get user's price alerts.
     */
    public function priceAlerts(User $user): JsonResponse
    {
        $priceAlerts = $user->priceAlerts()->with('product.category', 'product.brand')->get();

        return response()->json([
            'success' => true,
            'data' => $priceAlerts,
            'message' => 'User price alerts retrieved successfully',
        ]);
    }

    /**
     * Get user's reviews.
     */
    public function reviews(User $user): JsonResponse
    {
        $reviews = $user->reviews()->with('product.category', 'product.brand')->get();

        return response()->json([
            'success' => true,
            'data' => $reviews,
            'message' => 'User reviews retrieved successfully',
        ]);
    }

    private function applyUserFilters(Request $request, \Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        // Search by name or email
        if ($request->has('search')) {
            $searchInput = $request->get('search');
            $search = is_string($searchInput) ? $searchInput : '';
            $query->where(static function ($q) use ($search): void {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        // Filter by role (if role column exists) - Fixed SQL Injection vulnerability
        if ($request->has('role')) {
            $role = $request->get('role');
            if (is_string($role)) {
                $query->where('role', $role);
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('is_blocked', false);
            } elseif ($status === 'blocked') {
                $query->where('is_blocked', true);
            }
        }

        return $query;
    }
}
