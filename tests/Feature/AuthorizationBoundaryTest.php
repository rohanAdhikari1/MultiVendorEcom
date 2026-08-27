<?php

use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;

/**
 * A systematic sweep of the guest / customer / vendor / admin boundaries
 * across the app. Several of these are already covered incidentally by
 * other test files (e.g. EnsureVendorApprovedMiddlewareTest covers
 * vendor.dashboard for a guest and for a customer with no vendor profile);
 * this file exists to prove every route in each group behaves the same way
 * rather than assuming it from one example, and to fill the specific gaps
 * (orders confirmation route, the other two vendor.* routes, a vendor
 * blocked from /admin) that weren't already exercised anywhere.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('a guest is redirected to login for every /vendor/* route', function () {
    $this->get(route('vendor.dashboard'))->assertRedirect(route('login'));
    $this->get(route('vendor.products.index'))->assertRedirect(route('login'));
    $this->get(route('vendor.products.create'))->assertRedirect(route('login'));
    $this->get(route('vendor.orders.index'))->assertRedirect(route('login'));
});

test('a guest is redirected to login for the order confirmation route', function () {
    // No order needs to exist -- the auth middleware bounces the guest
    // before the route handler (and any authorization check) ever runs.
    $this->get(route('orders.confirmation', 'ORD-DOES-NOT-EXIST'))->assertRedirect(route('login'));
});

test('a customer with no vendor profile is redirected to apply for every /vendor/* route, not shown a 403', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.apply'));
    $this->actingAs($user)->get(route('vendor.products.index'))->assertRedirect(route('vendor.apply'));
    $this->actingAs($user)->get(route('vendor.products.create'))->assertRedirect(route('vendor.apply'));
    $this->actingAs($user)->get(route('vendor.orders.index'))->assertRedirect(route('vendor.apply'));
});

test('a pending (not yet approved) vendor is redirected to the status page for every /vendor/* route', function () {
    $user = User::factory()->create();
    $user->assignRole('vendor');
    VendorProfile::factory()->create(['user_id' => $user->id]); // default factory state is Pending

    $this->actingAs($user)->get(route('vendor.products.index'))->assertRedirect(route('vendor.status'));
    $this->actingAs($user)->get(route('vendor.products.create'))->assertRedirect(route('vendor.status'));
    $this->actingAs($user)->get(route('vendor.orders.index'))->assertRedirect(route('vendor.status'));
});

test('a vendor-role user (not admin) cannot access the Filament admin panel', function () {
    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $vendorUser->id]);
    $vendorUser->refresh();

    $this->actingAs($vendorUser)->get('/admin')->assertForbidden();
});
