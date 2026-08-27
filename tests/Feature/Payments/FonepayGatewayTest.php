<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\Drivers\FonepayGateway;
use Illuminate\Http\Request;

beforeEach(function () {
    config()->set('services.fonepay.base_url', 'https://clientapi.fonepay.com/api/merchantRequest');
    config()->set('services.fonepay.merchant_code', 'TEST-MERCHANT');
    config()->set('services.fonepay.secret_key', 'test-fonepay-secret');
});

function makeFonepayTestOrder(array $overrides = []): Order
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
        'payment_method' => PaymentMethod::Fonepay,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::PendingPayment,
        'placed_at' => now(),
    ], $overrides));
}

test('initiate builds a redirect URL with the correct HMAC-SHA512 checksum', function () {
    $order = makeFonepayTestOrder();

    $gateway = new FonepayGateway;
    $result = $gateway->initiate($order);

    expect($result->type)->toBe('redirect');
    expect($result->url)->toStartWith('https://clientapi.fonepay.com/api/merchantRequest?');

    parse_str(parse_url($result->url, PHP_URL_QUERY), $query);

    expect($query['PID'])->toBe('TEST-MERCHANT');
    expect($query['MD'])->toBe('P');
    expect($query['PRN'])->toBe($order->order_number);
    expect($query['AMT'])->toBe('1100.00');
    expect($query['CRN'])->toBe('NPR');

    $expectedDv = hash_hmac('sha512', implode(',', [
        $query['PID'], $query['MD'], $query['PRN'], $query['AMT'], $query['CRN'],
        $query['DT'], $query['R1'], $query['R2'], $query['RU'],
    ]), 'test-fonepay-secret');

    expect($query['DV'])->toBe($expectedDv);
});

test('verify accepts a correctly checksummed successful response', function () {
    $order = makeFonepayTestOrder();

    $raw = [
        'PRN' => $order->order_number,
        'PID' => 'TEST-MERCHANT',
        'PS' => 'true',
        'RC' => '00',
        'UID' => 'fonepay-uid-1',
        'BC' => 'NIC',
        'INI' => 'esewa',
        'P_AMT' => '1100.00',
        'R_AMT' => '0',
    ];

    $raw['DV'] = hash_hmac('sha512', implode(',', [
        $raw['PRN'], $raw['PID'], $raw['PS'], $raw['RC'], $raw['UID'],
        $raw['BC'], $raw['INI'], $raw['P_AMT'], $raw['R_AMT'],
    ]), 'test-fonepay-secret');

    $transaction = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Fonepay,
        'amount' => $order->grand_total,
        'status' => PaymentTransactionStatus::Initiated,
        'raw_response' => $raw,
    ]);

    $gateway = new FonepayGateway;

    expect($gateway->verify($transaction))->toBeTrue();
});

test('verify rejects a tampered checksum even when PS claims success', function () {
    $order = makeFonepayTestOrder();

    $raw = [
        'PRN' => $order->order_number,
        'PID' => 'TEST-MERCHANT',
        'PS' => 'true',
        'RC' => '00',
        'UID' => 'fonepay-uid-1',
        'BC' => 'NIC',
        'INI' => 'esewa',
        'P_AMT' => '1100.00',
        'R_AMT' => '0',
        'DV' => 'this-is-not-a-real-checksum',
    ];

    $transaction = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Fonepay,
        'amount' => $order->grand_total,
        'status' => PaymentTransactionStatus::Initiated,
        'raw_response' => $raw,
    ]);

    $gateway = new FonepayGateway;

    expect($gateway->verify($transaction))->toBeFalse();
});

test('verify rejects a correctly checksummed but non-successful PS value', function () {
    $order = makeFonepayTestOrder();

    $raw = [
        'PRN' => $order->order_number,
        'PID' => 'TEST-MERCHANT',
        'PS' => 'false',
        'RC' => '01',
        'UID' => 'fonepay-uid-1',
        'BC' => 'NIC',
        'INI' => 'esewa',
        'P_AMT' => '0',
        'R_AMT' => '0',
    ];

    $raw['DV'] = hash_hmac('sha512', implode(',', [
        $raw['PRN'], $raw['PID'], $raw['PS'], $raw['RC'], $raw['UID'],
        $raw['BC'], $raw['INI'], $raw['P_AMT'], $raw['R_AMT'],
    ]), 'test-fonepay-secret');

    $transaction = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'gateway' => PaymentMethod::Fonepay,
        'amount' => $order->grand_total,
        'status' => PaymentTransactionStatus::Initiated,
        'raw_response' => $raw,
    ]);

    $gateway = new FonepayGateway;

    expect($gateway->verify($transaction))->toBeFalse();
});

test('handleCallback parses the response params structurally without trusting them', function () {
    $order = makeFonepayTestOrder();

    $request = Request::create('/payments/fonepay/callback', 'GET', [
        'PRN' => $order->order_number,
        'PID' => 'TEST-MERCHANT',
        'PS' => 'true',
        'RC' => '00',
        'UID' => 'fonepay-uid-1',
        'BC' => 'NIC',
        'INI' => 'esewa',
        'P_AMT' => '1100.00',
        'R_AMT' => '0',
        'DV' => 'irrelevant-here',
    ]);

    $gateway = new FonepayGateway;
    $result = $gateway->handleCallback($request);

    expect($result->orderNumber)->toBe($order->order_number);
    expect($result->gatewayReference)->toBe('fonepay-uid-1');
    // handleCallback never asserts success on its own — verify() is authoritative.
    expect($result->success)->toBeFalse();
});
