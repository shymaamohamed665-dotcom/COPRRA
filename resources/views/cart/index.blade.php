@extends('layouts.app')

@section('title', __('messages.cart') . ' - ' . config('app.name'))

@section('content')
<div class="container py-4">
    <h1 class="mb-4">{{ __('messages.cart') }}</h1>

    @if($cartItems->isEmpty())
        <div class="text-center text-muted py-5">{{ __('messages.empty_cart') }}</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>{{ __('messages.product') }}</th>
                        <th style="width: 140px;">{{ __('messages.quantity') }}</th>
                        <th>{{ __('messages.price') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cartItems as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ $item->attributes->get('image') }}" alt="{{ $item->name }}" style="width:60px;height:60px;object-fit:cover" class="rounded me-3">
                                    <div>
                                        <div class="fw-semibold">{{ $item->name }}</div>
                                        <div class="text-muted small">#{{ $item->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('cart.update') }}" class="d-flex gap-2">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $item->id }}">
                                    <label for="quantity-{{ $item->id }}" class="sr-only">Quantity for {{ $item->name }}</label>
                                    <input name="quantity" type="number" id="quantity-{{ $item->id }}" value="{{ $item->quantity }}" min="1" class="form-control form-control-sm" style="width:80px" aria-label="Quantity for {{ $item->name }}">
                                    <button class="btn btn-sm btn-outline-primary" type="submit" aria-label="Update quantity for {{ $item->name }}">{{ __('messages.update') }}</button>
                                </form>
                            </td>
                            <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('cart.remove', $item->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('messages.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <form method="POST" action="{{ route('cart.clear') }}">
                @csrf
                <button class="btn btn-outline-secondary" type="submit">{{ __('messages.cart_cleared') }}</button>
            </form>
            <div class="fs-5">
                <span class="me-2 fw-semibold">{{ __('messages.total') }}:</span>
                <span>${{ number_format($total, 2) }}</span>
            </div>
        </div>
    @endif
</div>
@endsection
