<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_wishlist(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $wishlist = Wishlist::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notes' => 'Great product!',
        ]);

        $this->assertInstanceOf(Wishlist::class, $wishlist);
        $this->assertEquals($user->id, $wishlist->user_id);
        $this->assertEquals($product->id, $wishlist->product_id);
        $this->assertEquals('Great product!', $wishlist->notes);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notes' => 'Great product!',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->assertInstanceOf(User::class, $wishlist->user);
        $this->assertEquals($user->id, $wishlist->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_product(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $wishlist->product);
        $this->assertEquals($product->id, $wishlist->product->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_for_user(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product2->id]);
        Wishlist::factory()->create(['user_id' => $user2->id, 'product_id' => $product1->id]);

        $user1Wishlists = Wishlist::forUser($user1->id)->get();

        $this->assertCount(2, $user1Wishlists);
        $this->assertTrue($user1Wishlists->every(fn ($w) => $w->user_id === $user1->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_for_product(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user2->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product2->id]);

        $product1Wishlists = Wishlist::forProduct($product1->id)->get();

        $this->assertCount(2, $product1Wishlists);
        $this->assertTrue($product1Wishlists->every(fn ($w) => $w->product_id === $product1->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_product_in_wishlist(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);

        $this->assertTrue(Wishlist::isProductInWishlist($user->id, $product1->id));
        $this->assertFalse(Wishlist::isProductInWishlist($user->id, $product2->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_add_to_wishlist(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $wishlist = Wishlist::addToWishlist($user->id, $product->id, 'Nice product');

        $this->assertInstanceOf(Wishlist::class, $wishlist);
        $this->assertEquals($user->id, $wishlist->user_id);
        $this->assertEquals($product->id, $wishlist->product_id);
        $this->assertEquals('Nice product', $wishlist->notes);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_remove_from_wishlist(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $removed = Wishlist::removeFromWishlist($user->id, $product->id);

        $this->assertTrue($removed);
        $this->assertFalse(Wishlist::isProductInWishlist($user->id, $product->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_get_wishlist_count(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product1->id]);
        Wishlist::factory()->create(['user_id' => $user1->id, 'product_id' => $product2->id]);
        Wishlist::factory()->create(['user_id' => $user2->id, 'product_id' => $product1->id]);

        $this->assertEquals(2, Wishlist::getWishlistCount($user1->id));
        $this->assertEquals(1, Wishlist::getWishlistCount($user2->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_passes_with_valid_data(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $wishlist = new Wishlist([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notes' => 'Test notes',
        ]);

        $this->assertTrue($wishlist->validate());
        $this->assertEmpty($wishlist->getErrors());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_fails_with_missing_required_fields(): void
    {
        $wishlist = new Wishlist;

        $this->assertFalse($wishlist->validate());
        $errors = $wishlist->getErrors();
        $this->assertArrayHasKey('user_id', $errors);
        $this->assertArrayHasKey('product_id', $errors);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_soft_deletes(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $wishlist->delete();

        $this->assertSoftDeleted('wishlists', ['id' => $wishlist->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'user_id',
            'product_id',
            'notes',
        ];

        $this->assertEquals($fillable, (new Wishlist)->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_factory_creates_valid_wishlist(): void
    {
        $wishlist = Wishlist::factory()->make();

        $this->assertInstanceOf(Wishlist::class, $wishlist);
        $this->assertIsInt($wishlist->user_id);
        $this->assertIsInt($wishlist->product_id);
        $this->assertIsString($wishlist->notes);
    }
}
