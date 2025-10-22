<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OrderController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::where('user_id', Auth::id());

        if ($request->has('status')) {
            /** @var string|int $statusValue */
            $statusValue = $request->status;
            $status = OrderStatus::tryFrom($statusValue);
            if ($status) {
                $query->where('status', $status);
            }
        }

        /** @var int $perPage */
        $perPage = $request->get('per_page', 15);
        $orders = $query->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $collection = OrderResource::collection($orders);
        $payload = $collection->response()->getData(true);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data' => $payload,
            'meta' => $payload['meta'] ?? null,
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        // Check if order belongs to authenticated user
        if ($order->user_id !== Auth::id()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $order->load(['items.product', 'user']);

        return $this->successResponse(new OrderResource($order), 'Order retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => 'required|integer|min:1',
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

        // Create order logic here
        $order = Order::create([
            'user_id' => Auth::id(),
            'order_number' => 'ORD-'.time().'-'.Auth::id(),
            'status' => OrderStatus::PENDING,
            'total_amount' => 0, // Calculate based on items
            'subtotal' => 0, // Calculate based on items
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'currency' => 'USD',
            'shipping_address' => json_encode($validated['shipping_address']),
            'billing_address' => json_encode($validated['billing_address']),
        ]);

        // Create order items
        $totalAmount = 0;
        /** @var array<array<string, int>> $items */
        $items = $validated['items'];
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];

            $product = \App\Models\Product::find($productId);
            if ($product) {
                $price = (float) $product->price;
                $subtotal = $price * $quantity;
                $totalAmount += $subtotal;

                $order->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total_price' => $subtotal,
                ]);
            }
        }

        $order->update([
            'subtotal' => $totalAmount,
            'total_amount' => $totalAmount,
        ]);

        return $this->successResponse(new OrderResource($order->load('items.product')), 'Order created successfully', 201);
    }
}
