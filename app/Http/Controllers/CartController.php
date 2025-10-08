<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCartRequest;
use Illuminate\Http\RedirectResponse;

/**
 * @mixin \Darryldecode\Cart\Cart
 * @mixin \Darryldecode\Cart\CartCondition
 */
class CartController extends Controller
{
    public function update(UpdateCartRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var \Darryldecode\Cart\Cart $cartInstance */
        $cartInstance = app('cart');
        $cartInstance->update($validated['id'], [
            'quantity' => [
                'relative' => false,
                'value' => $validated['quantity'],
            ],
        ]);

        return redirect()->back()->with('success', 'Cart updated!');
    }
}
