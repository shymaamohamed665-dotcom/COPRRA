@extends('layouts.app')

@section('title', 'User Profile')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6 text-center">User Profile</h2>

        <div class="space-y-2">
            <p><strong>Name:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
        </div>

        <div class="mt-6">
            <a href="{{ route('profile.edit') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit Profile</a>
        </div>
    </div>
@endsection