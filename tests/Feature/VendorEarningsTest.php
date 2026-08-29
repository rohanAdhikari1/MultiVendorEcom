<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\VendorSubOrderStatus;
use App\Livewire\Vendor\Earnings;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorPayout;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

/**
 * Creates a fresh Order + single OrderItem for the given vendor.
 */
function makeEarningsTestOrderItem(
    VendorProfile $vendor,
    VendorSubOrderStatus $status,
    ?int $payoutId = null,
    float $earning = 450.0,
): OrderItem {
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
        'subtotal' => $earning,
        'shipping_total' => 0,
        'discount_total' => 0,
        'grand_total' => $earning,
        'currency' => 'NPR',
        'payment_method' => PaymentMethod::Cod,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::Processing,
        'placed_at' => now(),
    ]);

    return OrderItem::query()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'product_name_snapshot' => 'Test product',
        'unit_price_snapshot' => $earning,
        'quantity' => 1,
        'line_total' => $earning,
        'commission_percent_snapshot' => 10,
        'commission_amount' => 50,
        'vendor_earning' => $earning,
        'vendor_status' => $status,
        'payout_id' => $payoutId,
    ]);
}

it('shows correct earnings totals for the authenticated vendor', function () {
    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    $vendor = VendorProfile::factory()->approved()->create(['user_id' => $vendorUser->id]);

    $paidPayout = VendorPayout::query()->create([
        'vendor_id' => $vendor->id,
        'period_start' => now()->subDays(20),
        'period_end' => now()->subDays(15),
        'total_earnings' => 450,
        'status' => PayoutStatus::Paid,
        'paid_at' => now()->subDays(14),
    ]);

    // Delivered, already paid out.
    makeEarningsTestOrderItem($vendor, VendorSubOrderStatus::Delivered, $paidPayout->id, 450);
    // Delivered, not yet in any payout batch.
    makeEarningsTestOrderItem($vendor, VendorSubOrderStatus::Delivered, null, 900);
    // Not delivered yet — excluded from every bucket.
    makeEarningsTestOrderItem($vendor, VendorSubOrderStatus::Shipped, null, 300);

    $response = Livewire::actingAs($vendorUser)->test(Earnings::class);

    $response->assertOk()
        ->assertSeeHtml('data-target-value="1350"') // lifetime: 450 + 900
        ->assertSeeHtml('data-target-value="450"') // paid out
        ->assertSeeHtml('data-target-value="900"') // pending payout
        ->assertSee('Paid');
});

it('never shows a second vendor\'s payout data', function () {
    $vendorUserA = User::factory()->create();
    $vendorUserA->assignRole('vendor');
    $vendorA = VendorProfile::factory()->approved()->create(['user_id' => $vendorUserA->id, 'shop_name' => 'Vendor A Shop']);

    $vendorUserB = User::factory()->create();
    $vendorUserB->assignRole('vendor');
    $vendorB = VendorProfile::factory()->approved()->create(['user_id' => $vendorUserB->id, 'shop_name' => 'Vendor B Shop']);

    VendorPayout::query()->create([
        'vendor_id' => $vendorA->id,
        'period_start' => now()->subDays(20),
        'period_end' => now()->subDays(15),
        'total_earnings' => 111,
        'status' => PayoutStatus::Paid,
        'paid_at' => now()->subDays(14),
    ]);

    $payoutB = VendorPayout::query()->create([
        'vendor_id' => $vendorB->id,
        'period_start' => now()->subDays(20),
        'period_end' => now()->subDays(15),
        'total_earnings' => 222,
        'status' => PayoutStatus::Paid,
        'paid_at' => now()->subDays(14),
    ]);

    $response = Livewire::actingAs($vendorUserA)->test(Earnings::class);

    $response->assertOk()
        ->assertSeeHtml('data-target-value="0"') // vendor A has no paid-out earnings of its own
        ->assertDontSeeHtml('data-target-value="222"');

    expect($payoutB->vendor_id)->toBe($vendorB->id);
});
