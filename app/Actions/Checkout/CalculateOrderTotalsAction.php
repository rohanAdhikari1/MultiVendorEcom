<?php

namespace App\Actions\Checkout;

use App\Models\CartItem;
use Illuminate\Support\Collection;

/**
 * Computes an order's totals from a set of cart items.
 *
 * Shipping is a flat Rs. 100 per order for Phase 1 (charged once the cart
 * is non-empty, regardless of how many vendors/items are in it) — real
 * per-vendor/weight/distance shipping rules are out of scope here and can
 * replace this without touching PlaceOrderAction's call site.
 *
 * discount_total is always 0 — coupon/discount logic is reserved for a
 * later phase.
 */
class CalculateOrderTotalsAction
{
    /**
     * Flat shipping fee (NPR) applied to any non-empty order.
     */
    public const FLAT_SHIPPING_TOTAL = 100.0;

    /**
     * @param  Collection<int, CartItem>  $cartItems
     * @return array{subtotal: float, shipping_total: float, discount_total: float, grand_total: float}
     */
    public function execute(Collection $cartItems): array
    {
        $subtotal = round((float) $cartItems->sum(
            fn (CartItem $item) => (float) $item->price_snapshot * $item->quantity
        ), 2);

        $shippingTotal = $subtotal > 0 ? self::FLAT_SHIPPING_TOTAL : 0.0;
        $discountTotal = 0.0;

        $grandTotal = round($subtotal + $shippingTotal - $discountTotal, 2);

        return [
            'subtotal' => $subtotal,
            'shipping_total' => $shippingTotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
        ];
    }
}
