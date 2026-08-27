<?php

use App\Enums\ProductStatus;
use App\Livewire\Shared\CartIndicator;
use App\Livewire\Vendor\Dashboard;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

/**
 * Proof-by-markup tests for Phase 2A's motion pass. Since this environment
 * can't screenshot-verify animations, these assert the actual HTML/Alpine
 * wiring is present in rendered output: the mini-cart drawer's structure,
 * the scroll-reveal wrapper on product grids, and the stat-counter's
 * target-value data attribute.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --- Mini-cart drawer -------------------------------------------------

test('the mini-cart drawer markup is present for an authenticated user with an empty cart', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    Livewire::actingAs($user)
        ->test(CartIndicator::class)
        ->assertSet('count', 0)
        ->assertSeeHtml('id="mini-cart-drawer"')
        ->assertSeeHtml('id="cart-indicator-trigger"')
        ->assertSeeHtml('id="mini-cart-close"')
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('x-on:keydown.escape.window')
        ->assertSee('Your cart is empty');
});

test('the mini-cart drawer lists items, subtotal, and view/checkout actions when the cart has items', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Trekking Socks', 'price' => 350]);

    $cart = Cart::query()->create(['user_id' => $user->id]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 3,
        'price_snapshot' => 350,
    ]);

    Livewire::actingAs($user)
        ->test(CartIndicator::class)
        ->assertSet('count', 3)
        ->assertSee('Trekking Socks')
        ->assertSee('Qty 3')
        ->assertSee('1,050.00')
        ->assertSee('View Cart')
        ->assertSee('Checkout')
        ->assertDontSee('Your cart is empty');
});

test('the mini-cart drawer is not rendered for a guest', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('id="mini-cart-drawer"', false);
});

test('the mini-cart drawer is mounted on the storefront layout for an authenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertSee('id="mini-cart-drawer"', false);
    $response->assertSee('id="cart-indicator-trigger"', false);
});

// --- Scroll-reveal wrapper ----------------------------------------------

test('the product catalog grid wraps each card in the scroll-reveal component', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    Product::factory()->create(['vendor_id' => $vendor->id]);

    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('x-data="scrollReveal"', false);
    $response->assertSee('x-intersect.once="reveal()"', false);
});

test('the vendor shop page grid wraps each card in the scroll-reveal component', function () {
    $vendor = VendorProfile::factory()->approved()->create(['shop_slug' => 'reveal-test-shop']);
    Product::factory()->create(['vendor_id' => $vendor->id]);

    $response = $this->get(route('store.show', 'reveal-test-shop'));

    $response->assertOk();
    $response->assertSee('x-data="scrollReveal"', false);
    $response->assertSee('x-intersect.once="reveal()"', false);
});

test('a scroll-reveal wrapper starts already revealed for prefers-reduced-motion users (Alpine-side), server markup stays static regardless', function () {
    // The reduced-motion branch is a client-side Alpine check (see
    // resources/js/app.js `scrollReveal`), so there's nothing server-side
    // to assert beyond the wrapper markup itself being present and
    // carrying the transition classes it needs either way.
    $vendor = VendorProfile::factory()->approved()->create();
    Product::factory()->create(['vendor_id' => $vendor->id]);

    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('transition-all duration-500 ease-out', false);
});

// --- Stat counter ---------------------------------------------------------

test('the vendor dashboard renders animated stat counters with the correct target values', function () {
    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    $vendor = VendorProfile::factory()->approved()->create(['user_id' => $vendorUser->id]);

    Product::factory()->count(2)->create(['vendor_id' => $vendor->id, 'status' => ProductStatus::Published]);

    Livewire::actingAs($vendorUser)
        ->test(Dashboard::class)
        ->assertSeeHtml('data-stat-counter')
        ->assertSeeHtml('data-target-value="2"')
        ->assertSeeHtml('data-target-value="0"');
});

test('the stat-counter component renders the raw target value in a data attribute and a formatted fallback', function () {
    $view = Blade::render('<x-ui.stat-counter :value="1234.5" prefix="Rs. " :decimals="2" />');

    expect($view)->toContain('data-target-value="1234.5"');
    expect($view)->toContain('x-data="statCounter({ value: 1234.5, decimals: 2, duration: 900 })"');
    expect($view)->toContain('1,234.50');
});
