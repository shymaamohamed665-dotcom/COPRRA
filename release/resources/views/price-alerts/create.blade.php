@extends('layouts.app')

@section('title', 'Create Price Alert - ' . config('app.name'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Create Price Alert</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('price-alerts.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="product_id" class="form-label">Product <span class="text-danger">*</span></label>
                            <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                                <option value="">Select a product</option>
                                @foreach($products ?? [] as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} - ${{ number_format($product->price, 2) }}
                                </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="target_price" class="form-label">Target Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control @error('target_price') is-invalid @enderror" 
                                       id="target_price" 
                                       name="target_price" 
                                       value="{{ old('target_price') }}" 
                                       step="0.01" 
                                       min="0.01" 
                                       required>
                            </div>
                            @error('target_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">We'll notify you when the price drops to or below this amount.</div>
                        </div>

                        <div class="mb-3">
                            <label for="notification_method" class="form-label">Notification Method</label>
                            <select class="form-select @error('notification_method') is-invalid @enderror" id="notification_method" name="notification_method">
                                <option value="email" {{ old('notification_method', 'email') == 'email' ? 'selected' : '' }}>Email</option>
                                <option value="browser" {{ old('notification_method') == 'browser' ? 'selected' : '' }}>Browser Notification</option>
                                <option value="both" {{ old('notification_method') == 'both' ? 'selected' : '' }}>Both Email & Browser</option>
                            </select>
                            @error('notification_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Enable this price alert
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Add any additional notes about this price alert...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('price-alerts.index') }}" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Price Alert</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const targetPriceInput = document.getElementById('target_price');
    
    // Update target price when product is selected
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const priceText = selectedOption.textContent;
            const priceMatch = priceText.match(/\$([\d,]+\.?\d*)/);
            if (priceMatch) {
                const currentPrice = parseFloat(priceMatch[1].replace(',', ''));
                targetPriceInput.placeholder = `Current price: $${currentPrice.toFixed(2)}`;
                targetPriceInput.max = currentPrice;
            }
        } else {
            targetPriceInput.placeholder = '';
            targetPriceInput.max = '';
        }
    });

    // Validate target price
    targetPriceInput.addEventListener('input', function() {
        const value = parseFloat(this.value);
        const max = parseFloat(this.max);
        
        if (value && max && value >= max) {
            this.setCustomValidity('Target price must be less than current price');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>
@endsection
