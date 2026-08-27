<?php

namespace App\Actions\Checkout\Exceptions;

use RuntimeException;

/**
 * Thrown when PlaceOrderAction re-validates a coupon that was applied
 * earlier in the checkout flow (e.g. via the "Apply" button) and finds it
 * is no longer usable by the time the order is actually placed -- it may
 * have expired, been deactivated, or hit its max-use cap in the meantime.
 * The order is never placed with a stale/invalid discount silently applied.
 */
class CouponNoLongerValidException extends RuntimeException {}
