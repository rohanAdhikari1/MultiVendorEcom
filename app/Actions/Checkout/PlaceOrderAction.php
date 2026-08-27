<?php

namespace App\Actions\Checkout;

use App\Actions\Checkout\Exceptions\CouponNoLongerValidException;
use App\Actions\Checkout\Exceptions\EmptyCartException;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\VendorSubOrderStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Commission\CommissionCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Turns the authenticated user's current cart into a placed Order: snapshots
 * the shipping address and every line item's price/commission split onto
 * OrderItem rows, then clears the cart. Runs inside a single DB transaction
 * so a failure partway through never leaves a half-created order or a
 * cart that was cleared without an order to show for it.
 */
class PlaceOrderAction
{
    public function __construct(
        private readonly CalculateOrderTotalsAction $calculateOrderTotals,
        private readonly CommissionCalculator $commissionCalculator,
    ) {}

    /**
     * @param  Coupon|null  $coupon  A coupon the customer applied earlier in
     *                               checkout. It is re-validated against the
     *                               fresh cart subtotal inside the same DB
     *                               transaction that places the order, so a
     *                               coupon that expired or hit its max-use
     *                               cap between "Apply" and "Place Order"
     *                               never silently applies a stale discount
     *                               — CouponNoLongerValidException is thrown
     *                               instead and no order is created.
     */
    public function execute(User $user, Address $address, PaymentMethod $paymentMethod, ?Coupon $coupon = null): Order
    {
        $cart = Cart::query()->where('user_id', $user->id)->with('items.product', 'items.vendor')->first();

        if ($cart === null || $cart->items->isEmpty()) {
            throw new EmptyCartException("Cannot place an order for user #{$user->id}: cart is empty.");
        }

        $items = $cart->items;

        return DB::transaction(function () use ($user, $address, $paymentMethod, $cart, $items, $coupon) {
            $appliedCoupon = null;

            if ($coupon !== null) {
                // Re-fetch so we're validating against the latest used_count
                // rather than a possibly-stale in-memory instance from when
                // the customer clicked "Apply" earlier in the request cycle.
                $freshCoupon = Coupon::query()->find($coupon->id);
                $subtotalForValidation = $this->calculateOrderTotals->execute($items)['subtotal'];

                if ($freshCoupon === null || ! $freshCoupon->isValidFor($subtotalForValidation)) {
                    throw new CouponNoLongerValidException(
                        "Coupon #{$coupon->id} ({$coupon->code}) is no longer valid at order-placement time."
                    );
                }

                $appliedCoupon = $freshCoupon;
            }

            $totals = $this->calculateOrderTotals->execute($items, $appliedCoupon);

            $order = Order::query()->create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'shipping_recipient_name' => $address->recipient_name,
                'shipping_phone' => $address->phone,
                'shipping_province' => $address->province->name,
                'shipping_district' => $address->district->name,
                'shipping_municipality' => $address->municipality,
                'shipping_ward_no' => $address->ward_no,
                'shipping_street_address' => $address->street_address,
                'subtotal' => $totals['subtotal'],
                'shipping_total' => $totals['shipping_total'],
                'discount_total' => $totals['discount_total'],
                'grand_total' => $totals['grand_total'],
                'currency' => 'NPR',
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::Pending,
                'status' => OrderStatus::PendingPayment,
                'placed_at' => now(),
            ]);

            foreach ($items as $item) {
                $lineTotal = round((float) $item->price_snapshot * $item->quantity, 2);
                $commission = $this->commissionCalculator->calculate($item->vendor, $lineTotal);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'vendor_id' => $item->vendor_id,
                    'product_name_snapshot' => $item->product->name,
                    'unit_price_snapshot' => $item->price_snapshot,
                    'quantity' => $item->quantity,
                    'line_total' => $lineTotal,
                    'commission_percent_snapshot' => $commission['percent'],
                    'commission_amount' => $commission['commission'],
                    'vendor_earning' => $commission['vendorEarning'],
                    'vendor_status' => VendorSubOrderStatus::Pending,
                ]);
            }

            // COD has no payment gate to wait on — it's confirmed the moment
            // it's placed. Online gateways stay PendingPayment until their
            // callback confirms payment (see PaymentCallbackController).
            if ($paymentMethod === PaymentMethod::Cod) {
                $order->status = OrderStatus::Processing;
                $order->save();
            }

            if ($appliedCoupon !== null) {
                // Atomic at the DB level -- a bare UPDATE ... SET used_count
                // = used_count + 1, not a check-then-write from PHP -- so a
                // max_uses: 1 coupon can't be double-spent by two concurrent
                // requests racing past the isValidFor() check above.
                Coupon::query()->whereKey($appliedCoupon->id)->increment('used_count');
            }

            $cart->items()->delete();

            return $order->fresh(['items']) ?? $order;
        });
    }
}
