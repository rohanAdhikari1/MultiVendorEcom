<?php

namespace App\Services\Payments\Drivers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\DTO\PaymentInitiationResult;
use App\Services\Payments\DTO\PaymentVerificationResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * eSewa ePay v2 — a redirect-via-auto-submitting-HTML-form flow.
 *
 * initiate() builds the signed form fields for the checkout page to render
 * as a self-submitting form (see resources/views/payments/esewa-redirect.blade.php).
 * handleCallback() decodes and locally re-verifies the base64 `data` query
 * param eSewa redirects back with. verify() is the authoritative check:
 * a fresh server-to-server call to eSewa's transaction status endpoint.
 */
class EsewaGateway implements PaymentGatewayContract
{
    public function initiate(Order $order): PaymentInitiationResult
    {
        $productCode = (string) config('services.esewa.product_code');
        $secretKey = (string) config('services.esewa.secret_key');

        $totalAmount = $this->formatAmount($order->grand_total);
        $transactionUuid = $order->order_number;

        $signedFieldNames = 'total_amount,transaction_uuid,product_code';
        $signature = $this->sign($totalAmount, $transactionUuid, $productCode, $secretKey);

        $callbackUrl = route('payments.callback', ['gateway' => 'esewa']);

        return PaymentInitiationResult::form(
            formAction: (string) config('services.esewa.form_url'),
            fields: [
                'amount' => $totalAmount,
                'tax_amount' => '0',
                'total_amount' => $totalAmount,
                'transaction_uuid' => $transactionUuid,
                'product_code' => $productCode,
                'product_service_charge' => '0',
                'product_delivery_charge' => '0',
                'success_url' => $callbackUrl,
                'failure_url' => $callbackUrl,
                'signed_field_names' => $signedFieldNames,
                'signature' => $signature,
            ],
        );
    }

    public function handleCallback(Request $request): PaymentVerificationResult
    {
        $data = $request->query('data', $request->input('data'));

        if (! is_string($data) || $data === '') {
            return new PaymentVerificationResult(
                success: false,
                message: 'No payment data received from eSewa (the customer likely cancelled).',
            );
        }

        $decodedJson = base64_decode($data, true);
        $payload = $decodedJson === false ? null : json_decode($decodedJson, true);

        if (! is_array($payload)) {
            return new PaymentVerificationResult(success: false, message: 'Malformed eSewa callback payload.');
        }

        $signedFieldNames = (string) ($payload['signed_field_names'] ?? '');
        $fieldNames = array_filter(array_map('trim', explode(',', $signedFieldNames)));

        $message = collect($fieldNames)
            ->map(fn (string $field) => (string) ($payload[$field] ?? ''))
            ->implode(',');

        $expectedSignature = base64_encode(hash_hmac(
            'sha256',
            $message,
            (string) config('services.esewa.secret_key'),
            true,
        ));

        $receivedSignature = (string) ($payload['signature'] ?? '');
        $signatureValid = $fieldNames !== [] && hash_equals($expectedSignature, $receivedSignature);

        $status = (string) ($payload['status'] ?? '');

        return new PaymentVerificationResult(
            success: $signatureValid && $status === 'COMPLETE',
            gatewayReference: isset($payload['transaction_code']) ? (string) $payload['transaction_code'] : null,
            amount: isset($payload['total_amount']) ? (string) $payload['total_amount'] : null,
            orderNumber: isset($payload['transaction_uuid']) ? (string) $payload['transaction_uuid'] : null,
            rawResponse: $payload,
            message: $signatureValid ? null : 'Signature mismatch on eSewa callback payload.',
        );
    }

    public function verify(PaymentTransaction $transaction): bool
    {
        $order = $transaction->order;

        $response = Http::get((string) config('services.esewa.status_url'), [
            'product_code' => (string) config('services.esewa.product_code'),
            'total_amount' => $this->formatAmount($transaction->amount),
            'transaction_uuid' => $order->order_number,
        ]);

        if ($response->failed()) {
            return false;
        }

        return $response->json('status') === 'COMPLETE';
    }

    private function sign(string $totalAmount, string $transactionUuid, string $productCode, string $secretKey): string
    {
        $message = "{$totalAmount},{$transactionUuid},{$productCode}";

        return base64_encode(hash_hmac('sha256', $message, $secretKey, true));
    }

    private function formatAmount(string|float $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
