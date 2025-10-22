<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePriceAlertRequest;
use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class PriceAlertController extends Controller
{
    private const UNAUTHORIZED_MESSAGE = 'Unauthorized action.';

    public function create(Request $request): \Illuminate\View\View
    {
        $product = null;
        if ($request->has('product_id')) {
            $product = app(Product::class)->findOrFail($request->input('product_id'));
        }

        return view('price-alerts.create', ['product' => $product]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePriceAlertRequest $request, PriceAlert $priceAlert, Guard $auth): \Illuminate\Http\RedirectResponse
    {
        if ($priceAlert->user_id !== $auth->id()) {
            abort(403, self::UNAUTHORIZED_MESSAGE);
        }

        $priceAlert->update([
            'target_price' => $request->input('target_price'),
            'repeat_alert' => $request->boolean('repeat_alert'),
        ]);

        return redirect()->route('price-alerts.index')
            ->with('success', 'Price alert updated successfully!');
    }
}
