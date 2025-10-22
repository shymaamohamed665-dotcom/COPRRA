<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Tests\DatabaseSetup;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use DatabaseSetup;

    private OrderController $controller;

    private OrderService|MockInterface $orderServiceMock;

    private PointsService|MockInterface $pointsServiceMock;

    private User $user;

    private Request $requestMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->orderServiceMock = Mockery::mock(OrderService::class);
        $this->pointsServiceMock = Mockery::mock(PointsService::class);
        $this->controller = Mockery::mock(OrderController::class, [$this->orderServiceMock, $this->pointsServiceMock])->makePartial();
        $this->user = User::factory()->create();
        $this->requestMock = Mockery::mock(Request::class)->makePartial();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_index_returns_orders_for_authenticated_user(): void
    {
        // Arrange
        $orders = \Illuminate\Database\Eloquent\Collection::make([['id' => 1]]);
        $this->requestMock->shouldReceive('user')->andReturn($this->user);
        $this->requestMock->shouldReceive('get')->with('limit', 10)->andReturn(10);
        $this->orderServiceMock->shouldReceive('getOrderHistory')
            ->with($this->user, 10)
            ->andReturn($orders);

        // Act
        $response = $this->controller->index($this->requestMock);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('orders', $data);
        $this->assertEquals($orders->toArray(), $data['orders']);
    }

    public function test_index_returns_unauthorized_for_unauthenticated_user(): void
    {
        // Arrange
        $this->requestMock->shouldReceive('user')->andReturn(null);

        // Act
        $response = $this->controller->index($this->requestMock);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['error' => 'Unauthorized'], $response->getData(true));
    }

    public function test_show_returns_order_with_loaded_relationships(): void
    {
        // Arrange
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('load')->with(['items.product', 'payments.paymentMethod'])->andReturnSelf();
        $order->shouldReceive('jsonSerialize')->andReturn(['id' => 1]);
        $order->shouldReceive('getAttribute')->with('user_id')->andReturn($this->user->id);
        $this->requestMock->shouldReceive('user')->andReturn($this->user);

        // Mock authorize (not used in controller but kept for compatibility)
        $this->controller->shouldReceive('authorize')->with('view', $order)->byDefault();

        // Act
        $response = $this->controller->show($this->requestMock, $order);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['order' => ['id' => 1]], $response->getData(true));
    }

    public function test_create_creates_order_and_awards_points_for_valid_request(): void
    {
        // Arrange
        $cartItems = [['product_id' => 1, 'quantity' => 2]];
        $addresses = ['shipping' => [], 'billing' => []];
        $validated = ['cart_items' => $cartItems, 'shipping_address' => [], 'billing_address' => []];
        $order = Mockery::mock(Order::class);
        $this->requestMock->shouldReceive('validate')
            ->with([
                'cart_items' => 'required|array',
                'cart_items.*.product_id' => 'required|exists:products,id',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'shipping_address' => 'required|array',
                'billing_address' => 'required|array',
            ])
            ->andReturn($validated);
        $this->requestMock->shouldReceive('user')->andReturn($this->user);
        $this->orderServiceMock->shouldReceive('createOrder')
            ->with($this->user, $cartItems, $addresses)
            ->andReturn($order);
        $order->shouldReceive('jsonSerialize')->andReturn(['id' => 1]);
        $this->pointsServiceMock->shouldReceive('awardPurchasePoints')
            ->with($order);

        // Act
        $response = $this->controller->create($this->requestMock);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals([
            'success' => true,
            'order' => ['id' => 1],
            'message' => 'تم إنشاء الطلب بنجاح',
        ], $response->getData(true));
    }

    public function test_create_returns_unauthorized_for_unauthenticated_user(): void
    {
        // Arrange
        $validated = ['cart_items' => [], 'shipping_address' => [], 'billing_address' => []];
        $this->requestMock->shouldReceive('validate')->andReturn($validated);
        $this->requestMock->shouldReceive('user')->andReturn(null);

        // Act
        $response = $this->controller->create($this->requestMock);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(['error' => 'Unauthorized'], $response->getData(true));
    }

    public function test_update_status_updates_status_and_sends_notification(): void
    {
        // Arrange
        $order = Mockery::mock(Order::class);
        $user = User::factory()->create();
        $validated = ['status' => 'processing'];
        $this->requestMock->shouldReceive('validate')
            ->with(['status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded'])
            ->andReturn($validated);
        $order->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $order->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->orderServiceMock->shouldReceive('updateOrderStatus')
            ->with($order, 'processing')
            ->andReturn(true);
        $order->shouldReceive('getAttribute')->with('user')->andReturn($user);

        // Act
        $response = $this->controller->updateStatus($this->requestMock, $order);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
        ], $response->getData(true));
        $this->assertTrue(\Illuminate\Support\Facades\DB::table('notifications')->where('user_id', $user->id)->exists());
    }

    public function test_update_status_returns_error_when_update_fails(): void
    {
        // Arrange
        $order = Mockery::mock(Order::class);
        $validated = ['status' => 'invalid'];
        $this->requestMock->shouldReceive('validate')->andReturn($validated);
        $order->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $this->orderServiceMock->shouldReceive('updateOrderStatus')
            ->with($order, 'invalid')
            ->andReturn(false);

        // Act
        $response = $this->controller->updateStatus($this->requestMock, $order);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals([
            'success' => false,
            'message' => 'لا يمكن تحديث حالة الطلب',
        ], $response->getData(true));
    }

    public function test_cancel_cancels_order_successfully(): void
    {
        // Arrange
        $order = Mockery::mock(Order::class);
        $validated = ['reason' => 'Changed mind'];
        $this->requestMock->shouldReceive('validate')
            ->with(['reason' => 'nullable|string|max:500'])
            ->andReturn($validated);
        $this->orderServiceMock->shouldReceive('cancelOrder')
            ->with($order, 'Changed mind')
            ->andReturn(true);

        // Act
        $response = $this->controller->cancel($this->requestMock, $order);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'success' => true,
            'message' => 'تم إلغاء الطلب بنجاح',
        ], $response->getData(true));
    }

    public function test_cancel_returns_error_when_cancel_fails(): void
    {
        // Arrange
        $order = Mockery::mock(Order::class);
        $validated = ['reason' => null];
        $this->requestMock->shouldReceive('validate')->andReturn($validated);
        $this->orderServiceMock->shouldReceive('cancelOrder')
            ->with($order, null)
            ->andReturn(false);

        // Act
        $response = $this->controller->cancel($this->requestMock, $order);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals([
            'success' => false,
            'message' => 'لا يمكن إلغاء الطلب',
        ], $response->getData(true));
    }

    public function test_show_throws_authorization_exception_when_not_authorized(): void
    {
        // Arrange
        $order = Mockery::mock(Order::class);
        $order->shouldReceive('getAttribute')->with('user_id')->andReturn(999);
        $this->requestMock->shouldReceive('user')->andReturn($this->user);

        // Act
        $response = $this->controller->show($this->requestMock, $order);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(['error' => 'Unauthorized'], $response->getData(true));
    }

    public function test_update_status_returns_error_for_invalid_transition(): void
    {
        // Arrange
        $order = Mockery::mock(Order::class);
        $validated = ['status' => 'processing'];
        $this->requestMock->shouldReceive('validate')
            ->with(['status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded'])
            ->andReturn($validated);
        $order->shouldReceive('getAttribute')->with('status')->andReturn('delivered'); // Invalid transition
        $this->orderServiceMock->shouldReceive('updateOrderStatus')
            ->with($order, 'processing')
            ->andReturn(false);

        // Act
        $response = $this->controller->updateStatus($this->requestMock, $order);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals([
            'success' => false,
            'message' => 'لا يمكن تحديث حالة الطلب',
        ], $response->getData(true));
    }

    public function test_cancel_returns_error_for_invalid_status(): void
    {
        // Arrange
        $order = Mockery::mock(Order::class);
        $validated = ['reason' => 'Changed mind'];
        $this->requestMock->shouldReceive('validate')
            ->with(['reason' => 'nullable|string|max:500'])
            ->andReturn($validated);
        $order->shouldReceive('getAttribute')->with('status')->andReturn('shipped'); // Can't cancel shipped
        $this->orderServiceMock->shouldReceive('cancelOrder')
            ->with($order, 'Changed mind')
            ->andReturn(false);

        // Act
        $response = $this->controller->cancel($this->requestMock, $order);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals([
            'success' => false,
            'message' => 'لا يمكن إلغاء الطلب',
        ], $response->getData(true));
    }
}
