<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Thin, unauthenticated landing point for every online gateway's
 * redirect-back callback. Deliberately not behind `auth` middleware: the
 * customer's browser arrives here mid-redirect from the gateway and may
 * not carry an active Laravel session depending on the gateway's redirect
 * behaviour. Ownership is established from the callback/order data itself
 * (order_number → Order), not from the session.
 *
 * The only thing ever allowed to flip Order::payment_status to Paid is a
 * true result from the driver's authoritative verify() call — the parsed
 * callback (handleCallback()) is used only to locate the order/transaction
 * and to carry the raw payload for the audit trail.
 */
class PaymentCallbackController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentGatewayManager $manager): RedirectResponse
    {
        $method = PaymentMethod::tryFrom($gateway);

        if ($method === null || $method === PaymentMethod::Cod) {
            abort(404);
        }

        $driver = $manager->driver($method);
        $result = $driver->handleCallback($request);

        if ($result->orderNumber === null) {
            return redirect()->route('orders.failed')->with('paymentError', $result->message ?? 'The payment callback could not be processed.');
        }

        $order = Order::query()->where('order_number', $result->orderNumber)->first();

        if ($order === null) {
            return redirect()->route('orders.failed')->with('paymentError', 'No matching order was found for this payment callback.');
        }

        $transaction = PaymentTransaction::query()
            ->where('order_id', $order->id)
            ->where('gateway', $method)
            ->latest('id')
            ->first();

        if ($transaction === null) {
            $transaction = PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'gateway' => $method,
                'amount' => $order->grand_total,
                'status' => PaymentTransactionStatus::Initiated,
            ]);
        }

        $transaction->gateway_reference = $result->gatewayReference ?? $transaction->gateway_reference;
        $transaction->raw_response = $result->rawResponse;

        $verified = $driver->verify($transaction);

        if ($verified) {
            $transaction->status = PaymentTransactionStatus::Success;
            $transaction->verified_at = Carbon::now();
            $transaction->save();

            $order->payment_status = PaymentStatus::Paid;

            if ($order->status === OrderStatus::PendingPayment) {
                $order->status = OrderStatus::Processing;
            }

            $order->save();

            return redirect()->route('orders.confirmation', $order->order_number);
        }

        $transaction->status = PaymentTransactionStatus::Failed;
        $transaction->save();

        $order->payment_status = PaymentStatus::Failed;
        $order->save();

        return redirect()->route('orders.failed', ['order' => $order->order_number]);
    }
}
