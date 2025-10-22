@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h1 class="text-2xl font-bold mb-6">Admin Dashboard</h1>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-blue-500 text-white p-6 rounded-lg">
                        <h3 class="text-lg font-semibold">Total Users</h3>
                        <p class="text-3xl font-bold">{{ $stats['users'] ?? 0 }}</p>
                    </div>
                    <div class="bg-green-500 text-white p-6 rounded-lg">
                        <h3 class="text-lg font-semibold">Total Products</h3>
                        <p class="text-3xl font-bold">{{ $stats['products'] ?? 0 }}</p>
                    </div>
                    <div class="bg-yellow-500 text-white p-6 rounded-lg">
                        <h3 class="text-lg font-semibold">Total Stores</h3>
                        <p class="text-3xl font-bold">{{ $stats['stores'] ?? 0 }}</p>
                    </div>
                    <div class="bg-purple-500 text-white p-6 rounded-lg">
                        <h3 class="text-lg font-semibold">Total Categories</h3>
                        <p class="text-3xl font-bold">{{ $stats['categories'] ?? 0 }}</p>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold mb-4">Recent Users</h2>
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        @if(isset($recentUsers) && $recentUsers->count() > 0)
                            <div class="space-y-2">
                                @foreach($recentUsers as $user)
                                    <div class="flex justify-between items-center">
                                        <span>{{ $user->name }}</span>
                                        <span class="text-sm text-gray-500">{{ $user->created_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">No recent users</p>
                        @endif
                    </div>
                </div>

                <!-- Recent Products -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Recent Products</h2>
                    <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        @if(isset($recentProducts) && $recentProducts->count() > 0)
                            <div class="space-y-2">
                                @foreach($recentProducts as $product)
                                    <div class="flex justify-between items-center">
                                        <span>{{ $product->name }}</span>
                                        <span class="text-sm text-gray-500">{{ $product->created_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">No recent products</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
