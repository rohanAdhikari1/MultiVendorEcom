<?php

namespace App\Services\Payments\Drivers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\DTO\PaymentInitiationResult;
use App\Services\Payments\DTO\PaymentVerificationResult;
use App\Services\Payments\Exceptions\PaymentGatewayException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Khalti KPG v2 (ePayment) — a server-side POST-then-redirect flow.
 *
 * initiate() calls Khalti's /epayment/initiate/ endpoint directly and
 * hands back the `payment_url` it returns for the checkout page to
 * redirect the browser to. handleCallback() reads the query params Khalti
 * appends to our return_url. verify() is authoritative: a fresh
 * server-to-server /epayment/lookup/ call — only its own "Completed"
 * status (never the callback's own status param) counts as paid.
 */
class KhaltiGateway implements PaymentGatewayContract
{
    public function initiate(Order $order): PaymentInitiationResult
    {
        $amountPaisa = (int) round((float) $order->grand_total * 100);

        $response = Http::withHeaders([
            'Authorization' => 'Key '.(string) config('services.khalti.secret_key'),
        ])->post($this->endpoint('epayment/initiate/'), [
            'return_url' => route('payments.callback', ['gateway' => 'khalti']),
            'website_url' => (string) config('app.url'),
            'amount' => $amountPaisa,
            'purchase_order_id' => $order->order_number,
            'purchase_order_name' => 'Order #'.$order->order_number,
            'customer_info' => [
                'name' => $order->shipping_recipient_name,
                'phone' => $order->shipping_phone,
            ],
        ]);

        $paymentUrl = $response->json('payment_url');

        if ($response->failed() || ! is_string($paymentUrl) || $paymentUrl === '') {
            throw new PaymentGatewayException('Khalti payment initiation failed: '.$response->body());
        }

        $pidx = $response->json('pidx');

        return PaymentInitiationResult::redirect(
            url: $paymentUrl,
            reference: is_string($pidx) ? $pidx : null,
        );
    }

    public function handleCallback(Request $request): PaymentVerificationResult
    {
        $pidx = $request->query('pidx');
        $purchaseOrderId = $request->query('purchase_order_id');
        $status = $request->query('status');

        if (! is_string($pidx) || $pidx === '') {
            return new PaymentVerificationResult(success: false, message: 'Missing pidx in Khalti callback.');
        }

        return new PaymentVerificationResult(
            success: $status === 'Completed',
            gatewayReference: $pidx,
            amount: is_string($request->query('total_amount')) ? $request->query('total_amount') : null,
            orderNumber: is_string($purchaseOrderId) ? $purchaseOrderId : null,
            rawResponse: $request->query(),
            message: $status === 'Completed' ? null : 'Khalti reported a non-completed status on the callback.',
        );
    }

    public function verify(PaymentTransaction $transaction): bool
    {
        $pidx = $transaction->gateway_reference;

        if (! is_string($pidx) || $pidx === '') {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Key '.(string) config('services.khalti.secret_key'),
        ])->post($this->endpoint('epayment/lookup/'), ['pidx' => $pidx]);

        if ($response->failed()) {
            return false;
        }

        return $response->json('status') === 'Completed';
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.khalti.base_url'), '/').'/'.$path;
    }
}
