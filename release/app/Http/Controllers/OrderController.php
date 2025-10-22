<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Order Controller
 *
 * Handles all order-related HTTP requests including creation, viewing, and status updates.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly PointsService $pointsService
    ) {
    }

    /**
     * Get all orders for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $limit = (int) ($request->get('limit', 10));
        $orders = $this->orderService->getOrderHistory($user, $limit);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'orders' => $orders->toArray(),
        ], 200);
    }

    /**
     * Get single order details
     *
     * @param  int  $id
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ($order->user_id !== ($user?->id ?? null)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $order->load(['items.product', 'payments.paymentMethod']);

        return response()->json([
            'order' => $order,
        ], 200);
    }

    /**
     * Create a new order
     */
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

    /**
     * Create order from current cart and redirect
     */
    public function storeFromCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.street' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.state' => 'nullable|string|max:100',
            'shipping_address.zip' => 'required|string|max:20',
            'shipping_address.country' => 'required|string|max:100',
            'billing_address' => 'required|array',
            'billing_address.street' => 'required|string|max:255',
            'billing_address.city' => 'required|string|max:100',
            'billing_address.state' => 'nullable|string|max:100',
            'billing_address.zip' => 'required|string|max:20',
            'billing_address.country' => 'required|string|max:100',
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect('/login');
        }

        /** @var \Darryldecode\Cart\Cart $cart */
        $cart = app('cart');
        $content = $cart->getContent();

        $cartItems = [];
        foreach ($content as $item) {
            $cartItems[] = [
                'product_id' => (int) $item->id,
                'quantity' => (int) $item->quantity,
            ];
        }

        // Create order via service
        $order = $this->orderService->createOrder(
            $user,
            $cartItems,
            [
                'shipping' => $validated['shipping_address'],
                'billing' => $validated['billing_address'],
            ]
        );

        // Award points for purchase
        $this->pointsService->awardPurchasePoints($order);

        // Clear cart
        $cart->clear();

        // Redirect to order details
        return redirect()->route('orders.show', ['order' => $order->id]);
    }

    /**
     * Update order status
     *
     * @param  int  $id
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        $status = (string) ($validated['status'] ?? '');

        $updated = $this->orderService->updateOrderStatus($order, $status);

        if (! $updated) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديث حالة الطلب',
            ], 400);
        }

        $user = $order->getAttribute('user');
        if ($user) {
            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => 'order_status',
                'user_id' => $user->id,
                'notifiable_type' => $user::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'تحديث حالة الطلب',
                    'message' => 'تم تحديث حالة الطلب إلى '.($status !== '' && $status !== '0' ? $status : 'غير معروف'),
                    'order_id' => $order->id,
                    'status' => $status,
                ], JSON_UNESCAPED_UNICODE),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
        ], 200);
    }

    /**
     * Cancel an order
     *
     * @param  int  $id
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reason = $validated['reason'] ?? null;
        $reasonString = is_string($reason) ? $reason : null;

        $cancelled = $this->orderService->cancelOrder($order, $reasonString);

        if (! $cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء الطلب',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب بنجاح',
        ], 200);
    }
}
