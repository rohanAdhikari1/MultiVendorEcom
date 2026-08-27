<?php

use App\Actions\Orders\RecalculateOrderStatusAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\VendorSubOrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeOrderWithItemStatuses(array $vendorStatuses): Order
{
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = Order::query()->create([
        'order_number' => Order::generateOrderNumber(),
        'user_id' => $user->id,
        'shipping_recipient_name' => 'Jane Doe',
        'shipping_phone' => '9800000000',
        'shipping_province' => 'Bagmati',
        'shipping_district' => 'Kathmandu',
        'shipping_municipality' => 'Kathmandu Metro',
        'shipping_ward_no' => '5',
        'shipping_street_address' => 'Baneshwor',
        'subtotal' => 1000,
        'shipping_total' => 100,
        'discount_total' => 0,
        'grand_total' => 1100,
        'currency' => 'NPR',
        'payment_method' => PaymentMethod::Cod,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::Processing,
        'placed_at' => now(),
    ]);

    foreach ($vendorStatuses as $status) {
        $vendor = VendorProfile::factory()->approved()->create();

        OrderItem::query()->create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'product_name_snapshot' => 'Test product',
            'unit_price_snapshot' => 500,
            'quantity' => 1,
            'line_total' => 500,
            'commission_percent_snapshot' => 10,
            'commission_amount' => 50,
            'vendor_earning' => 450,
            'vendor_status' => $status,
        ]);
    }

    return $order->fresh(['items']);
}

it('sets the order to Delivered when every item is delivered', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Delivered, VendorSubOrderStatus::Delivered]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

it('sets the order to Cancelled when every item is cancelled', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Cancelled, VendorSubOrderStatus::Cancelled]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

it('sets the order to PartiallyShipped when some items are shipped and others still pending', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Shipped, VendorSubOrderStatus::Pending]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::PartiallyShipped);
});

it('sets the order to PartiallyShipped when some items are delivered and others still processing', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Delivered, VendorSubOrderStatus::Processing]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::PartiallyShipped);
});

it('sets the order to Shipped when every active item is shipped or delivered (none pending/processing)', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Shipped, VendorSubOrderStatus::Shipped]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

it('sets the order to Shipped for a mix of shipped and delivered items', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Shipped, VendorSubOrderStatus::Delivered]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

it('leaves the order as Processing while items are still pending/processing', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Pending, VendorSubOrderStatus::Processing]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

it('excludes a cancelled line item from an otherwise fully delivered order', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Delivered, VendorSubOrderStatus::Cancelled]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

it('derives Processing for a single-item order still pending', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Pending]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

it('derives Processing for a single-item order still processing', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Processing]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

it('derives Shipped for a single-item order that has shipped', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Shipped]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);
});

it('derives Delivered for a single-item order that has been delivered', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Delivered]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered);
});

it('derives Cancelled for a single-item order that was cancelled', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Cancelled]);

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
});

it('never touches payment_status', function () {
    $order = makeOrderWithItemStatuses([VendorSubOrderStatus::Delivered]);
    $order->payment_status = PaymentStatus::Paid;
    $order->save();

    app(RecalculateOrderStatusAction::class)->execute($order);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});
