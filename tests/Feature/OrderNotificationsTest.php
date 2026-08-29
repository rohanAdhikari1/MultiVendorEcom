<?php

use App\Actions\Orders\RecalculateOrderStatusAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\VendorSubOrderStatus;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Vendor\OrderManager;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\District;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Province;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderPaymentConfirmed;
use App\Notifications\OrderStatusUpdated;
use Database\Seeders\NepalLocationSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(NepalLocationSeeder::class);
    $this->seed(SiteSettingSeeder::class);
});

/**
 * Builds a customer with a saved address, ready to exercise checkout flows
 * against. Mirrors the setup used in CheckoutTest/CheckoutCouponTest.
 */
function setUpNotificationTestCustomer(): array
{
    $user = User::factory()->create();
    $user->assignRole('customer');

    $province = Province::query()->first();
    $district = District::query()->where('province_id', $province->id)->first();
    $address = Address::query()->create([
        'user_id' => $user->id,
        'recipient_name' => 'Jane Doe',
        'phone' => '9800000000',
        'province_id' => $province->id,
        'district_id' => $district->id,
        'municipality' => 'Kathmandu Metro',
        'ward_no' => '5',
        'street_address' => 'Baneshwor',
        'is_default' => true,
    ]);

    return [$user, $address];
}

test('placing a multi-vendor order notifies each vendor with their own item summary and earning', function () {
    Notification::fake();

    [$user, $address] = setUpNotificationTestCustomer();

    $vendorAUser = User::factory()->create();
    $vendorAUser->assignRole('vendor');
    $vendorA = VendorProfile::factory()->approved()->create(['user_id' => $vendorAUser->id, 'commission_override_percent' => 20]);
    $productA = Product::factory()->create(['vendor_id' => $vendorA->id, 'price' => 1000]);

    $vendorBUser = User::factory()->create();
    $vendorBUser->assignRole('vendor');
    $vendorB = VendorProfile::factory()->approved()->create(['user_id' => $vendorBUser->id, 'commission_override_percent' => null]);
    $productB = Product::factory()->create(['vendor_id' => $vendorB->id, 'price' => 500]);

    $cart = Cart::query()->create(['user_id' => $user->id]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $productA->id,
        'vendor_id' => $vendorA->id,
        'quantity' => 2,
        'price_snapshot' => $productA->price,
    ]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $productB->id,
        'vendor_id' => $vendorB->id,
        'quantity' => 1,
        'price_snapshot' => $productB->price,
    ]);

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('selectedAddressId', $address->id)
        ->set('paymentMethod', 'cod')
        ->call('placeOrder');

    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    // Vendor A: 2 x 1000 @ 20% commission override -> 1600 vendor earning.
    $itemA = $order->items()->where('vendor_id', $vendorA->id)->firstOrFail();
    expect((float) $itemA->vendor_earning)->toBe(1600.0);

    // Vendor B: 1 x 500 @ 10% default commission -> 450 vendor earning.
    $itemB = $order->items()->where('vendor_id', $vendorB->id)->firstOrFail();
    expect((float) $itemB->vendor_earning)->toBe(450.0);

    Notification::assertSentTo($vendorAUser, NewOrderReceived::class, function (NewOrderReceived $notification) use ($order, $vendorA) {
        $data = $notification->toArray($vendorA->user);

        return $notification->order->is($order)
            && $notification->vendor->is($vendorA)
            && $data['order_number'] === $order->order_number
            && str_contains($data['message'], '2 item')
            && str_contains($data['message'], number_format(1600, 2));
    });

    Notification::assertSentTo($vendorBUser, NewOrderReceived::class, function (NewOrderReceived $notification) use ($order, $vendorB) {
        $data = $notification->toArray($vendorB->user);

        return $notification->order->is($order)
            && $notification->vendor->is($vendorB)
            && $data['order_number'] === $order->order_number
            && str_contains($data['message'], '1 item')
            && str_contains($data['message'], number_format(450, 2));
    });

    // Each vendor was notified exactly once, and never received the other
    // vendor's notification instance.
    Notification::assertSentToTimes($vendorAUser, NewOrderReceived::class, 1);
    Notification::assertSentToTimes($vendorBUser, NewOrderReceived::class, 1);

    // Customer never receives the vendor-facing notification.
    Notification::assertNotSentTo($user, NewOrderReceived::class);
});

test('placing a COD order does not dispatch OrderPaymentConfirmed', function () {
    Notification::fake();

    [$user, $address] = setUpNotificationTestCustomer();

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 1000]);

    $cart = Cart::query()->create(['user_id' => $user->id]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 1,
        'price_snapshot' => $product->price,
    ]);

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('selectedAddressId', $address->id)
        ->set('paymentMethod', 'cod')
        ->call('placeOrder');

    Notification::assertNothingSentTo($user, OrderPaymentConfirmed::class);
});

test('a successful gateway payment callback dispatches OrderPaymentConfirmed to the customer', function () {
    Notification::fake();

    config()->set('services.khalti.base_url', 'https://dev.khalti.com/api/v2/');
    config()->set('services.khalti.secret_key', 'test-khalti-secret');

    $user = User::factory()->create();
    $order = Order::query()->create([
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
        'payment_method' => PaymentMethod::Khalti,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::PendingPayment,
        'placed_at' => now(),
    ]);

    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Khalti,
        'amount' => $order->grand_total,
        'gateway_reference' => 'pidx-xyz',
        'status' => PaymentTransactionStatus::Initiated,
    ]);

    Http::fake([
        'dev.khalti.com/api/v2/epayment/lookup/' => Http::response([
            'pidx' => 'pidx-xyz',
            'status' => 'Completed',
            'total_amount' => 110000,
        ]),
    ]);

    $this->get(route('payments.callback', ['gateway' => 'khalti']).'?'.http_build_query([
        'pidx' => 'pidx-xyz',
        'status' => 'Completed',
        'purchase_order_id' => $order->order_number,
        'total_amount' => '110000',
    ]));

    Notification::assertSentTo($user, OrderPaymentConfirmed::class, function (OrderPaymentConfirmed $notification) use ($order) {
        return $notification->order->is($order);
    });
});

test('a failed gateway payment callback does not dispatch OrderPaymentConfirmed', function () {
    Notification::fake();

    config()->set('services.khalti.base_url', 'https://dev.khalti.com/api/v2/');
    config()->set('services.khalti.secret_key', 'test-khalti-secret');

    $user = User::factory()->create();
    $order = Order::query()->create([
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
        'payment_method' => PaymentMethod::Khalti,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::PendingPayment,
        'placed_at' => now(),
    ]);

    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Khalti,
        'amount' => $order->grand_total,
        'gateway_reference' => 'pidx-xyz',
        'status' => PaymentTransactionStatus::Initiated,
    ]);

    Http::fake([
        'dev.khalti.com/api/v2/epayment/lookup/' => Http::response([
            'pidx' => 'pidx-xyz',
            'status' => 'Pending',
        ]),
    ]);

    $this->get(route('payments.callback', ['gateway' => 'khalti']).'?'.http_build_query([
        'pidx' => 'pidx-xyz',
        'status' => 'Completed',
        'purchase_order_id' => $order->order_number,
        'total_amount' => '110000',
    ]));

    Notification::assertNothingSentTo($user, OrderPaymentConfirmed::class);
});

test('a vendor marking the last remaining item Shipped dispatches OrderStatusUpdated to the customer exactly once, and not again on a no-op recalculation', function () {
    Notification::fake();

    $this->seed(RoleSeeder::class);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    $vendor = VendorProfile::factory()->approved()->create(['user_id' => $vendorUser->id]);

    $order = Order::query()->create([
        'order_number' => Order::generateOrderNumber(),
        'user_id' => $customer->id,
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

    $item = OrderItem::query()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'product_name_snapshot' => 'Test product',
        'unit_price_snapshot' => 1000,
        'quantity' => 1,
        'line_total' => 1000,
        'commission_percent_snapshot' => 10,
        'commission_amount' => 100,
        'vendor_earning' => 900,
        'vendor_status' => VendorSubOrderStatus::Pending,
    ]);

    Livewire::actingAs($vendorUser)
        ->test(OrderManager::class)
        ->call('updateStatus', $item->id, 'processing')
        ->call('updateStatus', $item->id, 'shipped');

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);

    Notification::assertSentTo($customer, OrderStatusUpdated::class, function (OrderStatusUpdated $notification) use ($order) {
        return $notification->order->is($order) && $notification->status === OrderStatus::Shipped;
    });
    Notification::assertSentToTimes($customer, OrderStatusUpdated::class, 1);

    // A subsequent no-op recalculation (status is already Shipped, nothing
    // changes) must not fire a duplicate notification.
    app(RecalculateOrderStatusAction::class)->execute($order->fresh(['items']));

    Notification::assertSentToTimes($customer, OrderStatusUpdated::class, 1);
});

test('an intermediate status transition (Pending to Processing) does not dispatch OrderStatusUpdated', function () {
    Notification::fake();

    $this->seed(RoleSeeder::class);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    $vendor = VendorProfile::factory()->approved()->create(['user_id' => $vendorUser->id]);

    $order = Order::query()->create([
        'order_number' => Order::generateOrderNumber(),
        'user_id' => $customer->id,
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

    $item = OrderItem::query()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'product_name_snapshot' => 'Test product',
        'unit_price_snapshot' => 1000,
        'quantity' => 1,
        'line_total' => 1000,
        'commission_percent_snapshot' => 10,
        'commission_amount' => 100,
        'vendor_earning' => 900,
        'vendor_status' => VendorSubOrderStatus::Pending,
    ]);

    Livewire::actingAs($vendorUser)
        ->test(OrderManager::class)
        ->call('updateStatus', $item->id, 'processing');

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);

    Notification::assertNothingSentTo($customer, OrderStatusUpdated::class);
});

test('placing a single-vendor order notifies that vendor', function () {
    Notification::fake();

    [$user, $address] = setUpNotificationTestCustomer();

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 1000]);

    $cart = Cart::query()->create(['user_id' => $user->id]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 1,
        'price_snapshot' => $product->price,
    ]);

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('selectedAddressId', $address->id)
        ->set('paymentMethod', 'cod')
        ->call('placeOrder');

    $this->assertDatabaseCount('orders', 1);
    Notification::assertSentTo($vendor->user, NewOrderReceived::class);
});
