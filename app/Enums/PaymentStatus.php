<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case OnsitePaid = 'onsite_paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'รอชำระ',
            self::Submitted => 'รอตรวจสลิป',
            self::Approved => 'ชำระแล้ว (อนุมัติสลิป)',
            self::Rejected => 'สลิปไม่ผ่าน',
            self::OnsitePaid => 'ชำระหน้างานแล้ว',
        };
    }

    /** WireKit badge intent */
    public function intent(): string
    {
        return match ($this) {
            self::Pending => 'neutral',
            self::Submitted => 'warning',
            self::Approved, self::OnsitePaid => 'success',
            self::Rejected => 'danger',
        };
    }

    public function isPaid(): bool
    {
        return in_array($this, self::paid(), true);
    }

    /** @return list<self> */
    public static function paid(): array
    {
        return [self::Approved, self::OnsitePaid];
    }
}
