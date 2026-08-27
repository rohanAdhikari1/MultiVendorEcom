<?php

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('an unauthenticated visitor is redirected to login', function () {
    $this->get(route('vendor.dashboard'))->assertRedirect(route('login'));
});

test('an authenticated user with no vendor profile is redirected to the application form', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.apply'));
});

test('a pending vendor is redirected to the status holding page, not the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('vendor');
    VendorProfile::factory()->create(['user_id' => $user->id, 'status' => VendorApplicationStatus::Pending]);

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.status'));
});

test('a rejected vendor is redirected to the status holding page, not the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('vendor');
    VendorProfile::factory()->create([
        'user_id' => $user->id,
        'status' => VendorApplicationStatus::Rejected,
        'rejected_reason' => 'Incomplete documents.',
    ]);

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.status'));
});

test('a suspended vendor is redirected to the status holding page, not the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('vendor');
    VendorProfile::factory()->create(['user_id' => $user->id, 'status' => VendorApplicationStatus::Suspended]);

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.status'));
});

test('an approved vendor reaches the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('vendor.dashboard'))->assertOk();
});

test('the status holding page shows the correct message per status and never lets an approved vendor see it', function () {
    $user = User::factory()->create();
    $user->assignRole('vendor');
    $vendorProfile = VendorProfile::factory()->approved()->create(['user_id' => $user->id]);

    // An approved vendor hitting the holding page directly bounces to the dashboard.
    $this->actingAs($user)->get(route('vendor.status'))->assertRedirect(route('vendor.dashboard'));

    $vendorProfile->update(['status' => VendorApplicationStatus::Rejected, 'rejected_reason' => 'Missing docs.']);

    // Re-fetch: $user's vendorProfile relation was cached Approved by the
    // request above, and Auth::actingAs() reuses that same PHP instance.
    $this->actingAs($user->fresh())->get(route('vendor.status'))
        ->assertOk()
        ->assertSee('not approved')
        ->assertSee('Missing docs.');
});
