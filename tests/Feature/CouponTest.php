<?php

use App\Enums\CouponType;
use App\Models\Coupon;

test('a percent coupon discounts the subtotal by its percentage', function () {
    $coupon = Coupon::factory()->percent(10)->create();

    expect($coupon->discountFor(1000.0))->toBe(100.0);
});

test('a fixed coupon discounts the subtotal by its flat NPR amount', function () {
    $coupon = Coupon::factory()->fixed(150)->create();

    expect($coupon->discountFor(1000.0))->toBe(150.0);
});

test('a fixed coupon larger than the subtotal caps the discount at the subtotal, never going negative', function () {
    $coupon = Coupon::factory()->fixed(500)->create();

    expect($coupon->discountFor(10.0))->toBe(10.0);
});

test('a percent coupon can never discount more than the subtotal itself', function () {
    $coupon = Coupon::factory()->percent(100)->create();

    expect($coupon->discountFor(250.0))->toBe(250.0);
});

test('code is always stored uppercase regardless of input casing', function () {
    $coupon = Coupon::factory()->create(['code' => 'save10']);

    expect($coupon->code)->toBe('SAVE10');
});

test('an active coupon within its window and under its usage cap is valid', function () {
    $coupon = Coupon::factory()->create([
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDay(),
        'max_uses' => 10,
        'used_count' => 3,
    ]);

    expect($coupon->isValidFor(1000.0))->toBeTrue();
});

test('an inactive coupon is not valid', function () {
    $coupon = Coupon::factory()->inactive()->create();

    expect($coupon->isValidFor(1000.0))->toBeFalse();
});

test('an expired coupon is not valid', function () {
    $coupon = Coupon::factory()->expired()->create();

    expect($coupon->isValidFor(1000.0))->toBeFalse();
});

test('a coupon that has not started yet is not valid', function () {
    $coupon = Coupon::factory()->notYetStarted()->create();

    expect($coupon->isValidFor(1000.0))->toBeFalse();
});

test('a coupon that has reached its max uses is not valid', function () {
    $coupon = Coupon::factory()->maxUsesReached(5)->create();

    expect($coupon->isValidFor(1000.0))->toBeFalse();
});

test('a coupon below its minimum order amount is not valid', function () {
    $coupon = Coupon::factory()->create(['min_order_amount' => 2000]);

    expect($coupon->isValidFor(1000.0))->toBeFalse();
    expect($coupon->isValidFor(2000.0))->toBeTrue();
});

test('coupon type casts to the CouponType enum', function () {
    $coupon = Coupon::factory()->fixed(50)->create();

    expect($coupon->type)->toBe(CouponType::Fixed);
});
