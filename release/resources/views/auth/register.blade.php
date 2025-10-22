@extends('layouts.app')

@section('title', __('messages.register') . ' - ' . config('app.name'))

@section('content')
<div class="container py-5" style="max-width:480px">
    <h1 class="h3 mb-4">{{ __('messages.register') }}</h1>
    <form method="POST" action="#">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">{{ __('messages.register') }}</button>
    </form>
</div>
@endsection
