<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;

/**
 */
class AdminController extends Controller
{
    public function dashboard(): View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! (bool) ($user->is_admin ?? false)) {
            return redirect()->route('home');
        }

        $stats = [
            'users' => User::count(),
            'products' => Product::count(),
        ];

        $recentUsers = User::latest()->take(5)->get();
        $recentProducts = Product::latest()->take(5)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentProducts' => $recentProducts,
        ]);
    }

    public function users(): View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! (bool) ($user->is_admin ?? false)) {
            return redirect()->route('home');
        }

        $users = User::latest()->paginate(15);

        return view('admin.users', [
            'users' => $users,
        ]);
    }
}
