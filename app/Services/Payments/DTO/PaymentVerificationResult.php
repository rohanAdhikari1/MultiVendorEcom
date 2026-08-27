<?php

namespace App\Services\Payments\DTO;

use App\Services\Payments\Contracts\PaymentGatewayContract;

/**
 * The parsed result of a gateway's redirect-back callback, produced by
 * {@see PaymentGatewayContract::handleCallback()}.
 *
 * This is intentionally a *local, structural* read of whatever the gateway
 * sent us on the redirect — it identifies which order the callback is
 * about and carries the raw payload for auditing, but it is NEVER treated
 * as authoritative proof of payment on its own. The caller must still call
 * {@see PaymentGatewayContract::verify()}
 * (a fresh server-to-server check) before marking an order paid.
 */
final readonly class PaymentVerificationResult
{
    /**
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public bool $success,
        public ?string $gatewayReference = null,
        public ?string $amount = null,
        public ?string $orderNumber = null,
        public array $rawResponse = [],
        public ?string $message = null,
    ) {}
}
