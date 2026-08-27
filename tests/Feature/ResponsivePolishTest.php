<?php

use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;

test('the storefront layout renders a mobile nav toggle and panel', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('id="mobile-nav-toggle"', false);
    $response->assertSee('id="mobile-nav-panel"', false);
    $response->assertSee('aria-label="Toggle navigation menu"', false);
});

test('the storefront layout mounts the global toast container', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('id="toast-container"', false);
    $response->assertSee('x-on:notify.window', false);
});

test('the vendor dashboard layout renders a mobile nav toggle and mounts the toast container', function () {
    $this->seed(RoleSeeder::class);

    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $vendorUser->id]);

    $response = $this->actingAs($vendorUser)->get(route('vendor.dashboard'));

    $response->assertOk();
    $response->assertSee('id="vendor-mobile-nav-toggle"', false);
    $response->assertSee('id="vendor-mobile-nav-panel"', false);
    $response->assertSee('id="toast-container"', false);
});

test('the product catalog grid collapses responsively and shows skeleton placeholders while loading', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    Product::factory()->create(['vendor_id' => $vendor->id]);

    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('grid-cols-2', false);
    $response->assertSee('sm:grid-cols-3', false);
    $response->assertSee('animate-pulse', false);
});
