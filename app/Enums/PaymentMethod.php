<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Esewa = 'esewa';
    case Khalti = 'khalti';
    case Fonepay = 'fonepay';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash on Delivery',
            self::Esewa => 'eSewa',
            self::Khalti => 'Khalti',
            self::Fonepay => 'Fonepay',
        };
    }
}
