<?php

namespace App\Services\Payments\Contracts;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\DTO\PaymentInitiationResult;
use App\Services\Payments\DTO\PaymentVerificationResult;
use Illuminate\Http\Request;

interface PaymentGatewayContract
{
    /**
     * Start a payment for the given order. Returns everything the checkout
     * page needs to send the customer onward to (or past) the gateway.
     */
    public function initiate(Order $order): PaymentInitiationResult;

    /**
     * Parse the gateway's redirect-back callback into a structural result:
     * which order it concerns, what reference/amount it reports, and the
     * raw payload for auditing. Local signature checks may be applied here
     * where the gateway supports it, but this is never sufficient on its
     * own to mark an order paid — see {@see verify()}.
     */
    public function handleCallback(Request $request): PaymentVerificationResult;

    /**
     * Authoritative, server-side confirmation that a transaction was
     * actually paid. Every online gateway must make (or, for Fonepay,
     * cryptographically validate) a real check here — this is the only
     * result allowed to flip Order::payment_status to Paid.
     */
    public function verify(PaymentTransaction $transaction): bool;
}
