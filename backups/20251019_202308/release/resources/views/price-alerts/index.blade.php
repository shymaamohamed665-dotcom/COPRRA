<!DOCTYPE html>
<html>
<head>
    <title>My Price Alerts</title>
</head>
<body>
    <h1>My Price Alerts</h1>

    @if($priceAlerts->isEmpty())
        <p>You have no active price alerts.</p>
    @else
        <ul>
            @foreach($priceAlerts as $alert)
                <li>{{ $alert->product->name }} - Target: {{ $alert->target_price }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
