<?php

use App\Livewire\Shared\AddToCartButton;
use App\Livewire\Shared\CartIndicator;
use App\Livewire\Storefront\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\VendorProfile;
use Livewire\Livewire;

test('an authenticated user adding a product to the cart creates a cart and cart item', function () {
    $user = User::factory()->create();
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'price' => 1500,
    ]);

    Livewire::actingAs($user)
        ->test(AddToCartButton::class, ['product' => $product])
        ->call('add');

    $this->assertDatabaseHas('carts', ['user_id' => $user->id]);

    $item = CartItem::query()->where('product_id', $product->id)->first();

    expect($item)->not->toBeNull();
    expect($item->quantity)->toBe(1);
    expect((float) $item->price_snapshot)->toBe(1500.0);
    expect($item->vendor_id)->toBe($vendor->id);
});

test('adding the same product again increments quantity rather than duplicating the row', function () {
    $user = User::factory()->create();
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 500]);

    Livewire::actingAs($user)->test(AddToCartButton::class, ['product' => $product])->call('add');
    Livewire::actingAs($user)->test(AddToCartButton::class, ['product' => $product])->call('add');

    $items = CartItem::query()->where('product_id', $product->id)->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->quantity)->toBe(2);
});

test('a guest cannot add to cart', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::test(AddToCartButton::class, ['product' => $product])->call('add');

    $this->assertDatabaseCount('carts', 0);
    $this->assertDatabaseCount('cart_items', 0);
});

test('the guest add-to-cart component renders a login link instead of the add action', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::test(AddToCartButton::class, ['product' => $product])
        ->assertSee('Log in to add to cart')
        ->assertDontSee('Add to Cart');
});

test('the cart indicator reflects the total quantity across cart items and updates on cart-updated', function () {
    $user = User::factory()->create();
    $vendor = VendorProfile::factory()->approved()->create();
    $productOne = Product::factory()->create(['vendor_id' => $vendor->id]);
    $productTwo = Product::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::actingAs($user)->test(AddToCartButton::class, ['product' => $productOne, 'quantity' => 2])->call('add');
    Livewire::actingAs($user)->test(AddToCartButton::class, ['product' => $productTwo])->call('add');

    Livewire::actingAs($user)
        ->test(CartIndicator::class)
        ->assertSet('count', 3);
});

test('adding a product with zero stock does not create a cart item', function () {
    $user = User::factory()->create();
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'stock_quantity' => 0]);

    Livewire::actingAs($user)
        ->test(AddToCartButton::class, ['product' => $product])
        ->call('add');

    $this->assertDatabaseCount('cart_items', 0);
});

test('adding a product variant with zero stock does not create a cart item, even though the base product has stock', function () {
    $user = User::factory()->create();
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'stock_quantity' => 50]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'V-OOS-1',
        'attributes' => ['size' => 'S'],
        'price_override' => null,
        'stock_quantity' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(AddToCartButton::class, ['product' => $product, 'productVariantId' => $variant->id])
        ->call('add');

    $this->assertDatabaseCount('cart_items', 0);
});

test('the add-to-cart component shows an Out of Stock badge instead of the add action when stock is zero', function () {
    $user = User::factory()->create();
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'stock_quantity' => 0]);

    Livewire::actingAs($user)
        ->test(AddToCartButton::class, ['product' => $product])
        ->assertSee('Out of Stock')
        ->assertDontSee('Add to Cart');
});

test('a price increase after a product is already in the cart does not change the frozen price_snapshot', function () {
    $user = User::factory()->create();
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 1000]);

    Livewire::actingAs($user)->test(AddToCartButton::class, ['product' => $product])->call('add');

    // The vendor raises the price after the item is already sitting in the cart.
    $product->update(['price' => 2500]);

    $item = CartItem::query()->where('product_id', $product->id)->firstOrFail();

    expect((float) $item->price_snapshot)->toBe(1000.0);

    Livewire::actingAs($user)
        ->test(Cart::class)
        ->assertSee('1,000.00')
        ->assertDontSee('2,500.00');
});
