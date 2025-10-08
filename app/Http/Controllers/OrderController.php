<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly PointsService $pointsService
    ) {}

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_items' => 'required|array',
            'cart_items.*.product_id' => 'required|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
            'billing_address' => 'required|array',
        ]);

        if (! is_array($validated)) {
            return response()->json(['error' => 'Invalid validation result'], 400);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cartItemsValue = $validated['cart_items'] ?? [];
        /** @var array<int, array{product_id: int, quantity: int}> $cartItems */
        $cartItems = is_array($cartItemsValue) ? $cartItemsValue : [];

        $shippingAddressValue = $validated['shipping_address'] ?? [];
        /** @var array<string, string> $shippingAddress */
        $shippingAddress = is_array($shippingAddressValue) ? $shippingAddressValue : [];

        $billingAddressValue = $validated['billing_address'] ?? [];
        /** @var array<string, string> $billingAddress */
        $billingAddress = is_array($billingAddressValue) ? $billingAddressValue : [];

        $order = $this->orderService->createOrder(
            $user,
            $cartItems,
            [
                'shipping' => $shippingAddress,
                'billing' => $billingAddress,
            ]
        );

        // Award points for purchase
        $this->pointsService->awardPurchasePoints($order);

        return response()->json([
            'success' => true,
            'order' => $order,
            'message' => 'تم إنشاء الطلب بنجاح',
        ], 201);
    }
}
