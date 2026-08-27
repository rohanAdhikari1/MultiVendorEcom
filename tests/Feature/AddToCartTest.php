<?php

use App\Livewire\Shared\AddToCartButton;
use App\Livewire\Shared\CartIndicator;
use App\Models\CartItem;
use App\Models\Product;
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

test('adding a product to the cart dispatches a success toast notification', function () {
    $user = User::factory()->create();
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::actingAs($user)
        ->test(AddToCartButton::class, ['product' => $product])
        ->call('add')
        ->assertDispatched('notify', message: 'Added to cart.', type: 'success');
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
