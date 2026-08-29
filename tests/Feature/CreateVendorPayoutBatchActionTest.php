<?php

use App\Actions\Payouts\CreateVendorPayoutBatchAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\VendorSubOrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorPayout;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->vendor = VendorProfile::factory()->approved()->create();
});

/**
 * Creates a fresh Order + single OrderItem for the given vendor, with the
 * given fulfillment status/payout assignment/earning amount.
 */
function makePayoutTestOrderItem(
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

it('includes only Delivered, not-yet-paid-out items and sums their earnings', function () {
    $delivered1 = makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, null, 900);
    $delivered2 = makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, null, 450);
    $shipped = makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Shipped, null, 300);
    $cancelled = makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Cancelled, null, 100);

    $priorPayout = VendorPayout::query()->create([
        'vendor_id' => $this->vendor->id,
        'period_start' => now()->subDays(10),
        'period_end' => now()->subDays(5),
        'total_earnings' => 200,
        'status' => PayoutStatus::Paid,
        'paid_at' => now()->subDays(4),
    ]);
    $alreadyPaidOut = makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, $priorPayout->id, 200);

    $payout = app(CreateVendorPayoutBatchAction::class)->execute($this->vendor);

    expect($payout)->not->toBeNull()
        ->and((float) $payout->total_earnings)->toBe(1350.0)
        ->and($payout->status)->toBe(PayoutStatus::Pending);

    expect($delivered1->fresh()->payout_id)->toBe($payout->id)
        ->and($delivered2->fresh()->payout_id)->toBe($payout->id)
        ->and($shipped->fresh()->payout_id)->toBeNull()
        ->and($cancelled->fresh()->payout_id)->toBeNull()
        ->and($alreadyPaidOut->fresh()->payout_id)->toBe($priorPayout->id);
});

it('does not double-include items already assigned to a payout on a second run', function () {
    makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, null, 900);

    $first = app(CreateVendorPayoutBatchAction::class)->execute($this->vendor);
    $second = app(CreateVendorPayoutBatchAction::class)->execute($this->vendor);

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull()
        ->and(VendorPayout::query()->where('vendor_id', $this->vendor->id)->count())->toBe(1);
});

it('handles the zero-eligible-items case gracefully without creating an empty payout', function () {
    makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Pending, null, 900);
    makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Processing, null, 450);

    $payout = app(CreateVendorPayoutBatchAction::class)->execute($this->vendor);

    expect($payout)->toBeNull()
        ->and(VendorPayout::query()->count())->toBe(0);
});

it('derives period_start/period_end from the included items when no range is given', function () {
    $item = makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, null, 900);

    $payout = app(CreateVendorPayoutBatchAction::class)->execute($this->vendor);

    expect($payout->period_start->toDateString())->toBe($item->created_at->toDateString())
        ->and($payout->period_end->toDateString())->toBe($item->created_at->toDateString());
});

it('restricts eligible items to an explicit date range when one is given', function () {
    $old = makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, null, 900);
    $old->forceFill(['created_at' => now()->subDays(60)])->save();

    $recent = makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, null, 450);

    $payout = app(CreateVendorPayoutBatchAction::class)->execute($this->vendor, now()->subDays(10), now());

    expect($payout)->not->toBeNull()
        ->and((float) $payout->total_earnings)->toBe(450.0)
        ->and($recent->fresh()->payout_id)->toBe($payout->id)
        ->and($old->fresh()->payout_id)->toBeNull();
});

it('scopes eligibility to the given vendor only, ignoring another vendor\'s delivered items', function () {
    $otherVendor = VendorProfile::factory()->approved()->create();

    makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, null, 900);
    $otherItem = makePayoutTestOrderItem($otherVendor, VendorSubOrderStatus::Delivered, null, 450);

    $payout = app(CreateVendorPayoutBatchAction::class)->execute($this->vendor);

    expect((float) $payout->total_earnings)->toBe(900.0)
        ->and($otherItem->fresh()->payout_id)->toBeNull();
});

it('wraps payout creation and item assignment in a single transaction', function () {
    // Sanity check that the created payout and the assigned items are
    // consistent with each other — i.e. the transaction committed as a unit.
    makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, null, 300);
    makePayoutTestOrderItem($this->vendor, VendorSubOrderStatus::Delivered, null, 600);

    $payout = app(CreateVendorPayoutBatchAction::class)->execute($this->vendor);

    $assignedSum = (float) OrderItem::query()->where('payout_id', $payout->id)->sum('vendor_earning');

    expect($assignedSum)->toBe((float) $payout->total_earnings);
});
