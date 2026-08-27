<?php

use App\Models\SiteSetting;
use App\Models\VendorProfile;
use App\Services\Commission\CommissionCalculator;

test('it uses the vendor\'s commission override when present', function () {
    $vendor = VendorProfile::factory()->approved()->create(['commission_override_percent' => 15]);

    $result = (new CommissionCalculator)->calculate($vendor, 1000.0);

    expect($result['percent'])->toBe(15.0);
    expect($result['commission'])->toBe(150.0);
    expect($result['vendorEarning'])->toBe(850.0);
});

test('it falls back to the platform default commission percent when the vendor has no override', function () {
    SiteSetting::query()->create(['id' => 1, 'default_commission_percent' => 12.5]);

    $vendor = VendorProfile::factory()->approved()->create(['commission_override_percent' => null]);

    $result = (new CommissionCalculator)->calculate($vendor, 1000.0);

    expect($result['percent'])->toBe(12.5);
    expect($result['commission'])->toBe(125.0);
    expect($result['vendorEarning'])->toBe(875.0);
});
