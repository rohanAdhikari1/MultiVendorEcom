<?php

use App\Livewire\Storefront\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('a guest is redirected to login when visiting the cart page', function () {
    $this->get(route('cart'))->assertRedirect(route('login'));
});

test('the cart page shows items already in the cart, grouped by vendor', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create(['shop_name' => 'Himalayan Bazaar']);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Wireless Earbuds', 'price' => 1200]);

    $cart = App\Models\Cart::query()->create(['user_id' => $user->id]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 2,
        'price_snapshot' => 1200,
    ]);

    Livewire::actingAs($user)
        ->test(Cart::class)
        ->assertSee('Wireless Earbuds')
        ->assertSee('Himalayan Bazaar')
        ->assertSee('2,400.00');
});

test('an empty cart renders the empty state', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    Livewire::actingAs($user)
        ->test(Cart::class)
        ->assertSee('Your cart is empty');
});

test('updating a quantity persists to the database and dispatches cart-updated', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 500]);

    $cart = App\Models\Cart::query()->create(['user_id' => $user->id]);
    $item = CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 1,
        'price_snapshot' => 500,
    ]);

    Livewire::actingAs($user)
        ->test(Cart::class)
        ->set("quantities.{$item->id}", 5)
        ->assertDispatched('cart-updated');

    expect($item->fresh()->quantity)->toBe(5);
});

test('removing an item deletes it from the cart and dispatches cart-updated', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 500]);

    $cart = App\Models\Cart::query()->create(['user_id' => $user->id]);
    $item = CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 1,
        'price_snapshot' => 500,
    ]);

    Livewire::actingAs($user)
        ->test(Cart::class)
        ->call('removeItem', $item->id)
        ->assertDispatched('cart-updated');

    $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
});

test('a user cannot update or remove another user\'s cart item', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $intruder->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 500]);

    $cart = App\Models\Cart::query()->create(['user_id' => $owner->id]);
    $item = CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 1,
        'price_snapshot' => 500,
    ]);

    Livewire::actingAs($intruder)
        ->test(Cart::class)
        ->call('removeItem', $item->id);

    $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
});
