<?php

namespace App\Services\Payments\Drivers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\DTO\PaymentInitiationResult;
use App\Services\Payments\DTO\PaymentVerificationResult;
use Illuminate\Http\Request;

/**
 * Cash on Delivery is not a gateway at all: there is nothing to redirect
 * to and nothing to verify online. Payment collection happens later,
 * out-of-band, when a vendor/admin marks the cash collected — that flow
 * is out of scope for this phase.
 */
class CashOnDeliveryGateway implements PaymentGatewayContract
{
    public function initiate(Order $order): PaymentInitiationResult
    {
        return PaymentInitiationResult::immediate();
    }

    public function handleCallback(Request $request): PaymentVerificationResult
    {
        // COD never redirects out to a gateway, so it never receives a
        // gateway callback either. Implemented only to satisfy the
        // contract; this should never actually be invoked.
        return new PaymentVerificationResult(success: true, message: 'Cash on Delivery has no gateway callback.');
    }

    public function verify(PaymentTransaction $transaction): bool
    {
        // Nothing to verify online — COD orders are confirmed immediately
        // by PlaceOrderAction and paid for at the door.
        return true;
    }
}
