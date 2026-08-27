<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\Drivers\EsewaGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.esewa.form_url', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
    config()->set('services.esewa.status_url', 'https://rc-epay.esewa.com.np/api/epay/transaction/status/');
    config()->set('services.esewa.product_code', 'EPAYTEST');
    config()->set('services.esewa.secret_key', 'test-secret-key');
});

function makeEsewaTestOrder(array $overrides = []): Order
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
        'payment_method' => PaymentMethod::Esewa,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::PendingPayment,
        'placed_at' => now(),
    ], $overrides));
}

test('initiate builds the correct signed form fields', function () {
    $order = makeEsewaTestOrder();

    $gateway = new EsewaGateway;
    $result = $gateway->initiate($order);

    expect($result->type)->toBe('form');
    expect($result->formAction)->toBe('https://rc-epay.esewa.com.np/api/epay/main/v2/form');

    $fields = $result->formFields;
    expect($fields['product_code'])->toBe('EPAYTEST');
    expect($fields['transaction_uuid'])->toBe($order->order_number);
    expect($fields['total_amount'])->toBe('1100.00');
    expect($fields['signed_field_names'])->toBe('total_amount,transaction_uuid,product_code');

    $expectedSignature = base64_encode(hash_hmac(
        'sha256',
        "1100.00,{$order->order_number},EPAYTEST",
        'test-secret-key',
        true,
    ));

    expect($fields['signature'])->toBe($expectedSignature);
});

test('handleCallback accepts a correctly signed success payload', function () {
    $order = makeEsewaTestOrder();

    $payload = [
        'transaction_code' => '000ABC',
        'status' => 'COMPLETE',
        'total_amount' => '1100.00',
        'transaction_uuid' => $order->order_number,
        'product_code' => 'EPAYTEST',
        'signed_field_names' => 'transaction_code,status,total_amount,transaction_uuid,product_code',
    ];

    $message = collect(explode(',', $payload['signed_field_names']))
        ->map(fn ($field) => $payload[$field])
        ->implode(',');

    $payload['signature'] = base64_encode(hash_hmac('sha256', $message, 'test-secret-key', true));

    $data = base64_encode(json_encode($payload));

    $request = Request::create('/payments/esewa/callback', 'GET', ['data' => $data]);

    $gateway = new EsewaGateway;
    $result = $gateway->handleCallback($request);

    expect($result->success)->toBeTrue();
    expect($result->orderNumber)->toBe($order->order_number);
    expect($result->gatewayReference)->toBe('000ABC');
});

test('handleCallback rejects a tampered signature', function () {
    $order = makeEsewaTestOrder();

    $payload = [
        'transaction_code' => '000ABC',
        'status' => 'COMPLETE',
        'total_amount' => '1100.00',
        'transaction_uuid' => $order->order_number,
        'product_code' => 'EPAYTEST',
        'signed_field_names' => 'transaction_code,status,total_amount,transaction_uuid,product_code',
        'signature' => base64_encode('not-the-real-signature'),
    ];

    $data = base64_encode(json_encode($payload));
    $request = Request::create('/payments/esewa/callback', 'GET', ['data' => $data]);

    $gateway = new EsewaGateway;
    $result = $gateway->handleCallback($request);

    expect($result->success)->toBeFalse();
});

test('verify calls the status endpoint and treats a COMPLETE response as paid', function () {
    $order = makeEsewaTestOrder();
    $transaction = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Esewa,
        'amount' => $order->grand_total,
        'status' => PaymentTransactionStatus::Initiated,
    ]);

    Http::fake([
        'rc-epay.esewa.com.np/api/epay/transaction/status/*' => Http::response(['status' => 'COMPLETE', 'ref_id' => 'REF123']),
    ]);

    $gateway = new EsewaGateway;
    expect($gateway->verify($transaction))->toBeTrue();
});

test('verify rejects a non-COMPLETE status endpoint response', function () {
    $order = makeEsewaTestOrder();
    $transaction = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Esewa,
        'amount' => $order->grand_total,
        'status' => PaymentTransactionStatus::Initiated,
    ]);

    Http::fake([
        'rc-epay.esewa.com.np/api/epay/transaction/status/*' => Http::response(['status' => 'PENDING']),
    ]);

    $gateway = new EsewaGateway;
    expect($gateway->verify($transaction))->toBeFalse();
});
