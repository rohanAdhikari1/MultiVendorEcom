<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Renders eSewa's auto-submitting HTML form redirect page. Checkout's
 * placeOrder() action sends the browser here (with the signed form fields
 * as a query string) rather than trying to render arbitrary HTML directly
 * from a Livewire component action.
 *
 * Only eSewa uses the "form" initiation shape in this app today — Khalti
 * and Fonepay redirect straight to the gateway instead.
 */
class PaymentFormRedirectController extends Controller
{
    public function __invoke(Request $request, string $gateway): View
    {
        abort_unless($gateway === 'esewa', 404);

        return view('payments.esewa-redirect', [
            'action' => (string) config('services.esewa.form_url'),
            'fields' => $request->query(),
        ]);
    }
}
