<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Price Alert</title>
    {{-- يمكنك إضافة روابط لملفات CSS هنا إذا أردت --}}
</head>
<body>

    @if (isset($priceAlert))
        <h1>Edit Alert for: {{ $priceAlert->product->name ?? 'Unknown Product' }}</h1>

        {{-- هذا النموذج هو ما سيصلح الاختبار --}}
        <form method="POST" action="{{ route('price-alerts.update', $priceAlert) }}">
            @csrf
            @method('PUT')

            <div>
                <label for="target_price">Target Price:</label>
                {{-- هذا هو الحقل الذي سيحتوي على السعر الذي يبحث عنه الاختبار --}}
                <input type="text" id="target_price" name="target_price" value="{{ old('target_price', $priceAlert->target_price) }}" required aria-label="Target price for alert">
                @error('target_price')
                    <div style="color: red;">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <input type="hidden" name="repeat_alert" value="0">
                <input type="checkbox" id="repeat_alert" name="repeat_alert" value="1" {{ old('repeat_alert', $priceAlert->repeat_alert) ? 'checked' : '' }} aria-label="Repeat alert when price is reached">
                <label for="repeat_alert">Repeat Alert</label>
            </div>

            <button type="submit">Update Alert</button>
        </form>

        <a href="{{ route('price-alerts.index') }}">Cancel</a>
    @else
        <h1>Price Alert Not Found</h1>
        <a href="{{ route('price-alerts.index') }}">Back to list</a>
    @endif

</body>
</html>
