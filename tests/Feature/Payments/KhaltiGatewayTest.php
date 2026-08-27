<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\Drivers\KhaltiGateway;
use App\Services\Payments\Exceptions\PaymentGatewayException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.khalti.base_url', 'https://dev.khalti.com/api/v2/');
    config()->set('services.khalti.secret_key', 'test-khalti-secret');
});

function makeKhaltiTestOrder(array $overrides = []): Order
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

test('initiate posts the amount in paisa and returns the payment url and pidx', function () {
    $order = makeKhaltiTestOrder();

    Http::fake([
        'dev.khalti.com/api/v2/epayment/initiate/' => Http::response([
            'pidx' => 'pidx-abc-123',
            'payment_url' => 'https://test-pay.khalti.com/?pidx=pidx-abc-123',
            'expires_at' => now()->addMinutes(60)->toIso8601String(),
            'expires_in' => 3600,
        ]),
    ]);

    $gateway = new KhaltiGateway;
    $result = $gateway->initiate($order);

    expect($result->type)->toBe('redirect');
    expect($result->url)->toBe('https://test-pay.khalti.com/?pidx=pidx-abc-123');
    expect($result->reference)->toBe('pidx-abc-123');

    Http::assertSent(function ($request) use ($order) {
        return $request->url() === 'https://dev.khalti.com/api/v2/epayment/initiate/'
            && $request['amount'] === 110000 // 1100.00 NPR -> paisa
            && $request['purchase_order_id'] === $order->order_number
            && $request->hasHeader('Authorization', 'Key test-khalti-secret');
    });
});

test('initiate throws when Khalti fails to return a payment url', function () {
    $order = makeKhaltiTestOrder();

    Http::fake([
        'dev.khalti.com/api/v2/epayment/initiate/' => Http::response(['detail' => 'Invalid amount'], 400),
    ]);

    $gateway = new KhaltiGateway;

    expect(fn () => $gateway->initiate($order))->toThrow(PaymentGatewayException::class);
});

test('handleCallback parses the redirect query params structurally', function () {
    $order = makeKhaltiTestOrder();

    $request = Request::create('/payments/khalti/callback', 'GET', [
        'pidx' => 'pidx-abc-123',
        'status' => 'Completed',
        'transaction_id' => 'txn-1',
        'purchase_order_id' => $order->order_number,
        'total_amount' => '110000',
    ]);

    $gateway = new KhaltiGateway;
    $result = $gateway->handleCallback($request);

    expect($result->orderNumber)->toBe($order->order_number);
    expect($result->gatewayReference)->toBe('pidx-abc-123');
});

test('verify treats a Completed lookup response as paid', function () {
    $order = makeKhaltiTestOrder();
    $transaction = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Khalti,
        'amount' => $order->grand_total,
        'gateway_reference' => 'pidx-abc-123',
        'status' => PaymentTransactionStatus::Initiated,
    ]);

    Http::fake([
        'dev.khalti.com/api/v2/epayment/lookup/' => Http::response([
            'pidx' => 'pidx-abc-123',
            'total_amount' => 110000,
            'status' => 'Completed',
            'transaction_id' => 'txn-1',
        ]),
    ]);

    $gateway = new KhaltiGateway;
    expect($gateway->verify($transaction))->toBeTrue();
});

test('verify rejects a non-Completed lookup response', function () {
    $order = makeKhaltiTestOrder();
    $transaction = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Khalti,
        'amount' => $order->grand_total,
        'gateway_reference' => 'pidx-abc-123',
        'status' => PaymentTransactionStatus::Initiated,
    ]);

    Http::fake([
        'dev.khalti.com/api/v2/epayment/lookup/' => Http::response([
            'pidx' => 'pidx-abc-123',
            'status' => 'Expired',
        ]),
    ]);

    $gateway = new KhaltiGateway;
    expect($gateway->verify($transaction))->toBeFalse();
});

test('verify rejects when the callback\'s own claimed status is Completed but the lookup says otherwise', function () {
    // This is the core "never trust the callback alone" check: even though
    // a malicious/forged callback might claim status=Completed, verify()
    // must ignore that and only trust its own fresh lookup call.
    $order = makeKhaltiTestOrder();
    $transaction = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Khalti,
        'amount' => $order->grand_total,
        'gateway_reference' => 'pidx-forged',
        'status' => PaymentTransactionStatus::Initiated,
    ]);

    Http::fake([
        'dev.khalti.com/api/v2/epayment/lookup/' => Http::response([
            'pidx' => 'pidx-forged',
            'status' => 'User canceled',
        ]),
    ]);

    $gateway = new KhaltiGateway;
    expect($gateway->verify($transaction))->toBeFalse();
});
