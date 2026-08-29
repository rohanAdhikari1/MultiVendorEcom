<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\VendorSubOrderStatus;
use App\Filament\Resources\VendorPayouts\Pages\ListVendorPayouts;
use App\Filament\Resources\VendorProfiles\Pages\ListVendorProfiles;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorPayout;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    // is_active is excluded from User::$fillable — refresh before actingAs()
    // so canAccessPanel() sees the real DB default rather than null.
    $this->admin->refresh();
});

it('loads the vendor payouts list for an admin', function (): void {
    $this->actingAs($this->admin)->get('/admin/vendor-payouts')->assertOk();
});

it('blocks a vendor from the vendor payouts admin page', function (): void {
    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $vendorUser->id]);
    $vendorUser->refresh();

    $this->actingAs($vendorUser)->get('/admin/vendor-payouts')->assertForbidden();
});

it('blocks a customer from the vendor payouts admin page', function (): void {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $customer->refresh();

    $this->actingAs($customer)->get('/admin/vendor-payouts')->assertForbidden();
});

it('lets an admin generate a payout batch for a vendor from the vendor profiles list', function (): void {
    $vendor = VendorProfile::factory()->approved()->create();

    $order = Order::query()->create([
        'order_number' => Order::generateOrderNumber(),
        'user_id' => User::factory()->create()->id,
        'shipping_recipient_name' => 'Jane Doe',
        'shipping_phone' => '9800000000',
        'shipping_province' => 'Bagmati',
        'shipping_district' => 'Kathmandu',
        'shipping_municipality' => 'Kathmandu Metro',
        'shipping_ward_no' => '5',
        'shipping_street_address' => 'Baneshwor',
        'subtotal' => 900,
        'shipping_total' => 0,
        'discount_total' => 0,
        'grand_total' => 900,
        'currency' => 'NPR',
        'payment_method' => PaymentMethod::Cod,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::Delivered,
        'placed_at' => now(),
    ]);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'product_name_snapshot' => 'Test product',
        'unit_price_snapshot' => 900,
        'quantity' => 1,
        'line_total' => 900,
        'commission_percent_snapshot' => 10,
        'commission_amount' => 100,
        'vendor_earning' => 900,
        'vendor_status' => VendorSubOrderStatus::Delivered,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListVendorProfiles::class)
        ->callTableAction('generatePayout', $vendor, data: [
            'period_start' => null,
            'period_end' => null,
        ]);

    $payout = VendorPayout::query()->where('vendor_id', $vendor->id)->first();

    expect($payout)->not->toBeNull()
        ->and((float) $payout->total_earnings)->toBe(900.0)
        ->and($payout->status)->toBe(PayoutStatus::Pending);
});

it('does not offer the generate payout action for a non-approved vendor', function (): void {
    $vendor = VendorProfile::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListVendorProfiles::class)
        ->assertTableActionHidden('generatePayout', $vendor);
});

it('lets an admin mark a pending payout as paid', function (): void {
    $vendor = VendorProfile::factory()->approved()->create();

    $payout = VendorPayout::query()->create([
        'vendor_id' => $vendor->id,
        'period_start' => now()->subDays(10),
        'period_end' => now()->subDays(3),
        'total_earnings' => 900,
        'status' => PayoutStatus::Pending,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListVendorPayouts::class)
        ->callTableAction('markPaid', $payout);

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatus::Paid)
        ->and($payout->paid_at)->not->toBeNull();
});

it('does not offer the mark paid action for an already-paid payout', function (): void {
    $vendor = VendorProfile::factory()->approved()->create();

    $payout = VendorPayout::query()->create([
        'vendor_id' => $vendor->id,
        'period_start' => now()->subDays(10),
        'period_end' => now()->subDays(3),
        'total_earnings' => 900,
        'status' => PayoutStatus::Paid,
        'paid_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListVendorPayouts::class)
        ->assertTableActionHidden('markPaid', $payout);
});
