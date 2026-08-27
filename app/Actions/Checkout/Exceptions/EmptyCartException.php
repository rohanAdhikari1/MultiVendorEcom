<?php

namespace App\Actions\Checkout\Exceptions;

use RuntimeException;

/**
 * Thrown if PlaceOrderAction is ever invoked for a user whose cart is
 * empty. The Checkout page guards against this before the user can even
 * reach the "place order" action, but the action re-checks defensively
 * rather than trusting the caller.
 */
class EmptyCartException extends RuntimeException {}
