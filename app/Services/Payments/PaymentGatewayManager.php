<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\Drivers\CashOnDeliveryGateway;
use App\Services\Payments\Drivers\EsewaGateway;
use App\Services\Payments\Drivers\FonepayGateway;
use App\Services\Payments\Drivers\KhaltiGateway;

/**
 * Resolves the correct gateway driver for a given payment method. A plain
 * match() over the four known drivers is all that's needed here — there's
 * no runtime extensibility requirement for this phase.
 */
class PaymentGatewayManager
{
    public function driver(PaymentMethod $method): PaymentGatewayContract
    {
        return match ($method) {
            PaymentMethod::Cod => app(CashOnDeliveryGateway::class),
            PaymentMethod::Esewa => app(EsewaGateway::class),
            PaymentMethod::Khalti => app(KhaltiGateway::class),
            PaymentMethod::Fonepay => app(FonepayGateway::class),
        };
    }
}
