<?php

namespace App\Services\Payments\Exceptions;

use RuntimeException;

/**
 * Thrown when a gateway driver cannot even begin a payment (e.g. the
 * gateway's initiate API call failed or returned an unusable response).
 */
class PaymentGatewayException extends RuntimeException {}
