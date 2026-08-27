<?php

use App\Enums\CouponType;
use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Models\Coupon;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // is_active is excluded from User::$fillable, so refresh before handing
    // the in-memory model to actingAs() -- see AdminResourceAccessTest.
    $this->admin->refresh();
});

it('creates a percent coupon from the admin panel', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateCoupon::class)
        ->fillForm([
            'code' => 'save10',
            'type' => CouponType::Percent->value,
            'value' => 10,
            'min_order_amount' => 500,
            'max_uses' => 100,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $coupon = Coupon::query()->where('code', 'SAVE10')->first();

    expect($coupon)->not->toBeNull()
        ->and($coupon->type)->toBe(CouponType::Percent)
        ->and((float) $coupon->value)->toBe(10.0)
        ->and((float) $coupon->min_order_amount)->toBe(500.0)
        ->and($coupon->max_uses)->toBe(100)
        ->and($coupon->is_active)->toBeTrue();
});

it('creates a fixed-amount coupon from the admin panel', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateCoupon::class)
        ->fillForm([
            'code' => 'FLAT150',
            'type' => CouponType::Fixed->value,
            'value' => 150,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $coupon = Coupon::query()->where('code', 'FLAT150')->first();

    expect($coupon)->not->toBeNull()
        ->and($coupon->type)->toBe(CouponType::Fixed)
        ->and((float) $coupon->value)->toBe(150.0);
});

it('rejects a duplicate coupon code', function (): void {
    Coupon::factory()->create(['code' => 'DUPE']);

    Livewire::actingAs($this->admin)
        ->test(CreateCoupon::class)
        ->fillForm([
            'code' => 'DUPE',
            'type' => CouponType::Percent->value,
            'value' => 10,
        ])
        ->call('create')
        ->assertHasFormErrors(['code']);
});

it('edits an existing coupon from the admin panel', function (): void {
    $coupon = Coupon::factory()->percent(10)->create(['is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(EditCoupon::class, ['record' => $coupon->getRouteKey()])
        ->fillForm([
            'value' => 25,
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $coupon->fresh()->value)->toBe(25.0)
        ->and($coupon->fresh()->is_active)->toBeFalse();
});
