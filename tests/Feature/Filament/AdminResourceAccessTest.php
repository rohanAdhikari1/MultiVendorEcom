<?php

use App\Models\User;
use Database\Seeders\MarketplaceDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SiteSettingSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(SiteSettingSeeder::class);
    $this->seed(MarketplaceDemoSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // `is_active` is deliberately excluded from User::$fillable (see
    // UserResource's CreateUser/EditUser pages), so the in-memory model
    // returned by factory()->create() never has it populated even though
    // the DB row gets the correct default. actingAs() reuses this exact
    // object as the authenticated user without refetching, so it must be
    // refreshed here or canAccessPanel() sees is_active as null.
    $this->admin->refresh();
});

it('loads the admin dashboard', function (): void {
    $this->actingAs($this->admin)->get('/admin')->assertOk();
});

it('loads the vendor profiles list', function (): void {
    $this->actingAs($this->admin)->get('/admin/vendor-profiles')->assertOk();
});

it('loads the categories list', function (): void {
    $this->actingAs($this->admin)->get('/admin/categories')->assertOk();
});

it('loads the products list', function (): void {
    $this->actingAs($this->admin)->get('/admin/products')->assertOk();
});

it('loads the orders list', function (): void {
    $this->actingAs($this->admin)->get('/admin/orders')->assertOk();
});

it('loads the users list', function (): void {
    $this->actingAs($this->admin)->get('/admin/users')->assertOk();
});

it('loads the content blocks list', function (): void {
    $this->actingAs($this->admin)->get('/admin/content-blocks')->assertOk();
});

it('loads the site settings page', function (): void {
    $this->actingAs($this->admin)->get('/admin/site-settings')->assertOk();
});

it('blocks a non-admin from the admin panel', function (): void {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $customer->refresh();

    $this->actingAs($customer)->get('/admin')->assertForbidden();
});
