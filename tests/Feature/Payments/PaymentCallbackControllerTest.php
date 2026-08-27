<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.khalti.base_url', 'https://dev.khalti.com/api/v2/');
    config()->set('services.khalti.secret_key', 'test-khalti-secret');
});

function makeCallbackTestOrder(array $overrides = []): Order
{
    $user = User::factory()->create();

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
        'payment_method' => PaymentMethod::Khalti,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::PendingPayment,
        'placed_at' => now(),
    ], $overrides));
}

test('a verified Khalti callback marks the order paid and processing, and redirects to confirmation', function () {
    $order = makeCallbackTestOrder();
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

    $response = $this->get(route('payments.callback', ['gateway' => 'khalti']).'?'.http_build_query([
        'pidx' => 'pidx-xyz',
        'status' => 'Completed',
        'purchase_order_id' => $order->order_number,
        'total_amount' => '110000',
    ]));

    $response->assertRedirect(route('orders.confirmation', $order->order_number));

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
    expect($order->status)->toBe(OrderStatus::Processing);

    $transaction = $order->paymentTransactions()->first();
    expect($transaction->status)->toBe(PaymentTransactionStatus::Success);
    expect($transaction->verified_at)->not->toBeNull();
});

test('a callback that fails authoritative verification never marks the order paid', function () {
    $order = makeCallbackTestOrder();
    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Khalti,
        'amount' => $order->grand_total,
        'gateway_reference' => 'pidx-xyz',
        'status' => PaymentTransactionStatus::Initiated,
    ]);

    // The lookup call — the only authoritative source — says it's still pending,
    // even though the redirect-back query string below forges status=Completed.
    Http::fake([
        'dev.khalti.com/api/v2/epayment/lookup/' => Http::response([
            'pidx' => 'pidx-xyz',
            'status' => 'Pending',
        ]),
    ]);

    $response = $this->get(route('payments.callback', ['gateway' => 'khalti']).'?'.http_build_query([
        'pidx' => 'pidx-xyz',
        'status' => 'Completed', // forged/optimistic client-side claim
        'purchase_order_id' => $order->order_number,
        'total_amount' => '110000',
    ]));

    $response->assertRedirect(route('orders.failed', ['order' => $order->order_number]));

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Failed);
    expect($order->status)->toBe(OrderStatus::PendingPayment);
});

test('an unknown gateway segment 404s', function () {
    $this->get(route('payments.callback', ['gateway' => 'not-a-real-gateway']))->assertNotFound();
});

test('cod is not a valid callback gateway', function () {
    $this->get(route('payments.callback', ['gateway' => 'cod']))->assertNotFound();
});
