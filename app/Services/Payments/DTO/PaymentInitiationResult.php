<?php

namespace App\Services\Payments\DTO;

use App\Services\Payments\Contracts\PaymentGatewayContract;

/**
 * What a checkout page needs in order to send the customer's browser
 * onward to (or past) a payment gateway, after {@see PaymentGatewayContract::initiate()}.
 *
 * Three shapes, one per gateway family in this app:
 * - `redirect`: a plain browser redirect to {@see $url} (Khalti, Fonepay).
 * - `form`: render an auto-submitting HTML form posting {@see $formFields}
 *   to {@see $formAction} (eSewa's ePay v2 flow).
 * - `immediate`: no gateway round trip needed at all (Cash on Delivery) —
 *   the order is already confirmed, go straight to the confirmation page.
 */
final readonly class PaymentInitiationResult
{
    /**
     * @param  'redirect'|'form'|'immediate'  $type
     * @param  array<string, string>|null  $formFields
     */
    private function __construct(
        public string $type,
        public ?string $url = null,
        public ?string $formAction = null,
        public ?array $formFields = null,
        public ?string $reference = null,
    ) {}

    /**
     * A gateway-side reference (e.g. Khalti's `pidx`) may optionally be
     * passed so the caller can persist it onto the PaymentTransaction
     * immediately, before any callback arrives.
     */
    public static function redirect(string $url, ?string $reference = null): self
    {
        return new self(type: 'redirect', url: $url, reference: $reference);
    }

    /**
     * @param  array<string, string>  $fields
     */
    public static function form(string $formAction, array $fields): self
    {
        return new self(type: 'form', formAction: $formAction, formFields: $fields);
    }

    public static function immediate(): self
    {
        return new self(type: 'immediate');
    }
}
