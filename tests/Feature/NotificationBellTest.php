<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Shared\NotificationBell;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderPaymentConfirmed;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeNotificationBellOrder(User $user): Order
{
    return Order::query()->create([
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
        'payment_method' => PaymentMethod::Khalti,
        'payment_status' => PaymentStatus::Paid,
        'status' => OrderStatus::Processing,
        'placed_at' => now(),
    ]);
}

test('the bell shows zero unread count when there are no notifications', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->assertSet('unreadCount', 0);
});

test('the bell shows the correct unread count and lists notifications', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = makeNotificationBellOrder($user);

    $user->notify(new OrderPaymentConfirmed($order));
    $user->notify(new OrderPaymentConfirmed($order));

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->assertSet('unreadCount', 2)
        ->assertSee($order->order_number);
});

test('markAsRead marks a single notification read and decrements the unread count', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = makeNotificationBellOrder($user);
    $user->notify(new OrderPaymentConfirmed($order));
    $user->notify(new OrderPaymentConfirmed($order));

    $notificationId = $user->notifications()->first()->id;

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->assertSet('unreadCount', 2)
        ->call('markAsRead', $notificationId)
        ->assertSet('unreadCount', 1);

    expect($user->notifications()->whereKey($notificationId)->first()->read_at)->not->toBeNull();
});

test('markAllAsRead marks every notification for the user as read', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $order = makeNotificationBellOrder($user);
    $user->notify(new OrderPaymentConfirmed($order));
    $user->notify(new OrderPaymentConfirmed($order));
    $user->notify(new OrderPaymentConfirmed($order));

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->assertSet('unreadCount', 3)
        ->call('markAllAsRead')
        ->assertSet('unreadCount', 0);

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('one user cannot mark another user\'s notification as read', function () {
    $userA = User::factory()->create();
    $userA->assignRole('customer');

    $userB = User::factory()->create();
    $userB->assignRole('customer');

    $order = makeNotificationBellOrder($userB);
    $userB->notify(new OrderPaymentConfirmed($order));

    $userBNotificationId = $userB->notifications()->first()->id;

    Livewire::actingAs($userA)
        ->test(NotificationBell::class)
        ->call('markAsRead', $userBNotificationId);

    // User A's scoped notifications() relation never matched User B's
    // notification id, so it was left untouched.
    expect($userB->notifications()->whereKey($userBNotificationId)->first()->read_at)->toBeNull();
});
