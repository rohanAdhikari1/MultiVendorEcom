<?php

use App\Enums\VendorApplicationStatus;
use App\Filament\Resources\VendorProfiles\Pages\ListVendorProfiles;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // `is_active` is excluded from User::$fillable, so the in-memory model
    // needs a refresh before being handed to actingAs()/Livewire::actingAs()
    // — see AdminResourceAccessTest for the full explanation.
    $this->admin->refresh();
});

it('approves a pending vendor application', function (): void {
    $vendor = VendorProfile::factory()->create();

    expect($vendor->status)->toBe(VendorApplicationStatus::Pending);

    Livewire::actingAs($this->admin)
        ->test(ListVendorProfiles::class)
        ->callTableAction('approve', $vendor);

    $vendor->refresh();

    expect($vendor->status)->toBe(VendorApplicationStatus::Approved)
        ->and($vendor->approved_at)->not->toBeNull();
});

it('rejects a pending vendor application with a reason', function (): void {
    $vendor = VendorProfile::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListVendorProfiles::class)
        ->callTableAction('reject', $vendor, data: [
            'rejected_reason' => 'Missing business registration documents.',
        ]);

    $vendor->refresh();

    expect($vendor->status)->toBe(VendorApplicationStatus::Rejected)
        ->and($vendor->rejected_reason)->toBe('Missing business registration documents.');
});

it('suspends an approved vendor', function (): void {
    $vendor = VendorProfile::factory()->approved()->create();

    Livewire::actingAs($this->admin)
        ->test(ListVendorProfiles::class)
        ->callTableAction('suspend', $vendor);

    $vendor->refresh();

    expect($vendor->status)->toBe(VendorApplicationStatus::Suspended);
});

it('does not offer the approve action for an already-approved vendor', function (): void {
    $vendor = VendorProfile::factory()->approved()->create();

    Livewire::actingAs($this->admin)
        ->test(ListVendorProfiles::class)
        ->assertTableActionHidden('approve', $vendor);
});
