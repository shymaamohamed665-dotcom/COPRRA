<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Alert Details</title>
    {{-- يمكنك إضافة روابط لملفات CSS هنا إذا أردت --}}
</head>
<body>

    <h1>Price Alert Details</h1>

    @if (isset($priceAlert))
        {{-- عرض تفاصيل المنتج --}}
        <h2>Product: {{ $priceAlert->product->name ?? 'Product Not Found' }}</h2>

        <ul>
            <li><strong>Target Price:</strong> {{ number_format($priceAlert->target_price, 2) }}</li>
            <li><strong>Status:</strong> {{ $priceAlert->is_active ? 'Active' : 'Inactive' }}</li>
            <li><strong>Alert Repeats:</strong> {{ $priceAlert->repeat_alert ? 'Yes' : 'No' }}</li>
        </ul>

        {{-- عرض أفضل عروض الأسعار المتوفرة (من دالة show في الـ Controller) --}}
        <h3>Current Offers:</h3>
        @if ($priceAlert->product && $priceAlert->product->priceOffers->isNotEmpty())
            <ul>
                @foreach ($priceAlert->product->priceOffers as $offer)
                    <li>
                        {{ $offer->seller_name }}: <strong>{{ number_format($offer->price, 2) }}</strong>
                        <em>(Last updated: {{ $offer->updated_at->diffForHumans() }})</em>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No current offers found for this product.</p>
        @endif

    @else
        <p>Price alert not found.</p>
    @endif

      

    <a href="{{ route('price-alerts.index') }}">Back to All Alerts</a>

</body>
</html>
