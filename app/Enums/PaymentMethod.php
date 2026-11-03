<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Transfer = 'transfer';
    case Onsite = 'onsite';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'โอนเงิน',
            self::Onsite => 'ชำระหน้างาน',
        };
    }
}
