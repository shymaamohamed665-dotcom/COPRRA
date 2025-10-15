<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCartRequest;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @mixin \Darryldecode\Cart\Cart
 * @mixin \Darryldecode\Cart\CartCondition
 */
class CartController extends Controller
{
    public function index(): View
    {
        /** @var \Darryldecode\Cart\Cart $cart */
        $cart = app('cart');

        return view('cart.index', [
            'cartItems' => $cart->getContent(),
            'total' => $cart->getTotal(),
        ]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => ['string', 'max:255'],
        ]);

        /** @var \Darryldecode\Cart\Cart $cart */
        $cart = app('cart');

        $attributes = array_merge($validated['attributes'] ?? [], [
            'slug' => $product->slug ?? null,
            'image' => $product->image ?? null,
        ]);

        $cart->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) ($product->price ?? 0),
            'quantity' => (int) $validated['quantity'],
            'attributes' => $attributes,
        ]);
        \Illuminate\Support\Facades\Session::flash('success', 'Product added to cart!');

        return redirect()->route('cart.index');
    }

    public function addFromRequest(Request $request): Response
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => ['string', 'max:255'],
        ]);

        /** @var Product $product */
        $product = Product::findOrFail((int) $validated['product_id']);

        /** @var \Darryldecode\Cart\Cart $cart */
        $cart = app('cart');

        $attributes = array_merge($validated['attributes'] ?? [], [
            'slug' => $product->slug ?? null,
            'image' => $product->image ?? null,
        ]);

        $cart->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) ($product->price ?? 0),
            'quantity' => (int) $validated['quantity'],
            'attributes' => $attributes,
        ]);

        return response('Created', 201);
    }

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
        \Illuminate\Support\Facades\Session::flash('success', 'Cart updated!');

        return redirect()->route('cart.index');
    }

    public function remove(string $itemId): RedirectResponse
    {
        /** @var \Darryldecode\Cart\Cart $cart */
        $cart = app('cart');
        $cart->remove($itemId);
        \Illuminate\Support\Facades\Session::flash('success', 'Item removed from cart!');

        return redirect()->route('cart.index');
    }

    public function clear(): RedirectResponse
    {
        /** @var \Darryldecode\Cart\Cart $cart */
        $cart = app('cart');
        $cart->clear();
        \Illuminate\Support\Facades\Session::flash('success', 'Cart cleared!');

        return redirect()->route('cart.index');
    }
}
