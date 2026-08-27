<?php

use App\Enums\VendorApplicationStatus;
use App\Livewire\Vendor\Dashboard;
use App\Livewire\Vendor\Onboarding\ApplicationForm;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('a guest is redirected to login when visiting the vendor application form', function () {
    $this->get(route('vendor.apply'))->assertRedirect(route('login'));
});

test('a plain customer can apply to become a vendor and lands on the holding page, not the dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    Livewire::actingAs($user)
        ->test(ApplicationForm::class)
        ->set('shopName', 'Kathmandu Crafts')
        ->set('shopSlug', 'kathmandu-crafts')
        ->set('description', 'Handmade goods from the valley.')
        ->call('apply')
        ->assertRedirect(route('vendor.dashboard'));

    $user->refresh();

    expect($user->hasRole('vendor'))->toBeTrue()
        ->and($user->hasRole('customer'))->toBeTrue();

    $vendorProfile = VendorProfile::query()->where('user_id', $user->id)->firstOrFail();

    expect($vendorProfile->status)->toBe(VendorApplicationStatus::Pending)
        ->and($vendorProfile->shop_name)->toBe('Kathmandu Crafts')
        ->and($vendorProfile->shop_slug)->toBe('kathmandu-crafts');

    // Following the post-apply redirect to /vendor, the approval middleware
    // sends a Pending vendor to the holding page instead of the dashboard.
    $this->actingAs($user)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.status'));
});

test('the vendor application form auto-slugifies the shop name unless manually edited', function () {
    Livewire::actingAs(tap(User::factory()->create())->assignRole('customer'))
        ->test(ApplicationForm::class)
        ->set('shopName', 'Everest Outfitters')
        ->assertSet('shopSlug', 'everest-outfitters');
});

test('applying with a taken shop slug fails validation', function () {
    VendorProfile::factory()->create(['shop_slug' => 'taken-slug']);

    $user = User::factory()->create();
    $user->assignRole('customer');

    Livewire::actingAs($user)
        ->test(ApplicationForm::class)
        ->set('shopName', 'Another Shop')
        ->set('shopSlug', 'taken-slug')
        ->call('apply')
        ->assertHasErrors(['shopSlug']);
});

test('a user who already has a vendor profile is redirected away from the application form', function () {
    $user = User::factory()->create();
    $user->assignRole('vendor');
    VendorProfile::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(ApplicationForm::class)
        ->assertRedirect(route('vendor.dashboard'));
});

test('an approved vendor reaches the dashboard after admin approval', function () {
    $user = User::factory()->create();
    $user->assignRole('vendor');
    $vendorProfile = VendorProfile::factory()->create(['user_id' => $user->id]);

    // Not approved yet — blocked from the dashboard.
    $this->actingAs($user)->get(route('vendor.dashboard'))->assertRedirect(route('vendor.status'));

    // Simulate the admin approval Filament action (VendorProfileResource).
    $vendorProfile->update([
        'status' => VendorApplicationStatus::Approved,
        'approved_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertOk();
});
