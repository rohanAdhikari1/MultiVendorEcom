<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Storefront\OrderConfirmation;
use App\Livewire\Storefront\OrderHistory;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeOrderHistoryTestOrder(User $user, array $overrides = []): Order
{
    return Order::query()->create(array_merge([
        'order_number' => Order::generateOrderNumber(),
        'user_id' => $user->id,
        'shipping_recipient_name' => 'Jane Doe',
        'shipping_phone' => '9800000000',
        'shipping_province' => 'Bagmati Province',
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
    ], $overrides));
}

test('a guest is redirected to login when visiting order history', function () {
    $this->get(route('orders.index'))->assertRedirect(route('login'));
});

test('order history shows only the authenticated user\'s own orders', function () {
    $userA = User::factory()->create();
    $userA->assignRole('customer');
    $userB = User::factory()->create();
    $userB->assignRole('customer');

    $orderA = makeOrderHistoryTestOrder($userA, ['order_number' => 'ORD-AAAA']);
    makeOrderHistoryTestOrder($userB, ['order_number' => 'ORD-BBBB']);

    Livewire::actingAs($userA)
        ->test(OrderHistory::class)
        ->assertSee('ORD-AAAA')
        ->assertDontSee('ORD-BBBB');
});

test('the order confirmation page is only viewable by its owner', function () {
    $owner = User::factory()->create();
    $owner->assignRole('customer');
    $intruder = User::factory()->create();
    $intruder->assignRole('customer');

    $order = makeOrderHistoryTestOrder($owner);

    Livewire::actingAs($owner)
        ->test(OrderConfirmation::class, ['orderNumber' => $order->order_number])
        ->assertSee($order->order_number);

    Livewire::actingAs($intruder)
        ->test(OrderConfirmation::class, ['orderNumber' => $order->order_number])
        ->assertForbidden();
});

test('an admin can view any order\'s confirmation page', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $order = makeOrderHistoryTestOrder($customer);

    Livewire::actingAs($admin)
        ->test(OrderConfirmation::class, ['orderNumber' => $order->order_number])
        ->assertSee($order->order_number);
});
