<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\VendorSubOrderStatus;
use App\Livewire\Vendor\OrderManager;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');

    $this->vendorUserA = User::factory()->create();
    $this->vendorUserA->assignRole('vendor');
    $this->vendorA = VendorProfile::factory()->approved()->create(['user_id' => $this->vendorUserA->id]);

    $this->vendorUserB = User::factory()->create();
    $this->vendorUserB->assignRole('vendor');
    $this->vendorB = VendorProfile::factory()->approved()->create(['user_id' => $this->vendorUserB->id]);

    $this->order = Order::query()->create([
        'order_number' => Order::generateOrderNumber(),
        'user_id' => $this->customer->id,
        'shipping_recipient_name' => 'Jane Doe',
        'shipping_phone' => '9800000000',
        'shipping_province' => 'Bagmati',
        'shipping_district' => 'Kathmandu',
        'shipping_municipality' => 'Kathmandu Metro',
        'shipping_ward_no' => '5',
        'shipping_street_address' => 'Baneshwor',
        'subtotal' => 1500,
        'shipping_total' => 100,
        'discount_total' => 0,
        'grand_total' => 1600,
        'currency' => 'NPR',
        'payment_method' => PaymentMethod::Cod,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::Processing,
        'placed_at' => now(),
    ]);

    $this->itemA = OrderItem::query()->create([
        'order_id' => $this->order->id,
        'vendor_id' => $this->vendorA->id,
        'product_name_snapshot' => 'Vendor A product',
        'unit_price_snapshot' => 1000,
        'quantity' => 1,
        'line_total' => 1000,
        'commission_percent_snapshot' => 10,
        'commission_amount' => 100,
        'vendor_earning' => 900,
        'vendor_status' => VendorSubOrderStatus::Pending,
    ]);

    $this->itemB = OrderItem::query()->create([
        'order_id' => $this->order->id,
        'vendor_id' => $this->vendorB->id,
        'product_name_snapshot' => 'Vendor B product',
        'unit_price_snapshot' => 500,
        'quantity' => 1,
        'line_total' => 500,
        'commission_percent_snapshot' => 10,
        'commission_amount' => 50,
        'vendor_earning' => 450,
        'vendor_status' => VendorSubOrderStatus::Pending,
    ]);
});

test('a vendor only sees their own line items within a shared multi-vendor order', function () {
    $response = Livewire::actingAs($this->vendorUserA)->test(OrderManager::class);

    $response->assertSee('Vendor A product');
    $response->assertDontSee('Vendor B product');
});

test('a vendor updating their own item persists the new status and recalculates the parent order', function () {
    Livewire::actingAs($this->vendorUserA)
        ->test(OrderManager::class)
        ->call('updateStatus', $this->itemA->id, 'processing');

    expect($this->itemA->fresh()->vendor_status)->toBe(VendorSubOrderStatus::Processing);
    // Item B is still Pending, so the order stays Processing (no regression either way).
    expect($this->order->fresh()->status)->toBe(OrderStatus::Processing);
});

test('a successful status update dispatches a success toast notification', function () {
    Livewire::actingAs($this->vendorUserA)
        ->test(OrderManager::class)
        ->call('updateStatus', $this->itemA->id, 'processing')
        ->assertDispatched('notify', message: 'Order status updated to Processing.', type: 'success');
});

test('an invalid status transition does not dispatch a toast notification', function () {
    Livewire::actingAs($this->vendorUserA)
        ->test(OrderManager::class)
        ->call('updateStatus', $this->itemA->id, 'delivered')
        ->assertNotDispatched('notify');
});

test('once every vendor sub-order is shipped the parent order status becomes Shipped', function () {
    Livewire::actingAs($this->vendorUserA)
        ->test(OrderManager::class)
        ->call('updateStatus', $this->itemA->id, 'processing')
        ->call('updateStatus', $this->itemA->id, 'shipped');

    Livewire::actingAs($this->vendorUserB)
        ->test(OrderManager::class)
        ->call('updateStatus', $this->itemB->id, 'processing')
        ->call('updateStatus', $this->itemB->id, 'shipped');

    expect($this->order->fresh()->status)->toBe(OrderStatus::Shipped);
});

test('a vendor cannot mutate another vendor\'s line item in the same shared order', function () {
    Livewire::actingAs($this->vendorUserA)
        ->test(OrderManager::class)
        ->call('updateStatus', $this->itemB->id, 'processing')
        ->assertForbidden();

    expect($this->itemB->fresh()->vendor_status)->toBe(VendorSubOrderStatus::Pending);
});

test('an invalid status transition is silently ignored', function () {
    Livewire::actingAs($this->vendorUserA)
        ->test(OrderManager::class)
        ->call('updateStatus', $this->itemA->id, 'delivered');

    // Pending -> Delivered is not an allowed direct transition.
    expect($this->itemA->fresh()->vendor_status)->toBe(VendorSubOrderStatus::Pending);
});

test('a vendor with no items in any order sees an empty list', function () {
    $lonelyVendorUser = User::factory()->create();
    $lonelyVendorUser->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $lonelyVendorUser->id]);

    Livewire::actingAs($lonelyVendorUser)
        ->test(OrderManager::class)
        ->assertSee('No orders yet');
});
