<?php

namespace App\Enums;

enum PaymentTransactionStatus: string
{
    case Initiated = 'initiated';
    case Success = 'success';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Initiated => 'Initiated',
            self::Success => 'Success',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }
}
