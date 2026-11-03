<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Coordinator = 'coordinator';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'ผู้ดูแลระบบ',
            self::Staff => 'เจ้าหน้าที่เช็กอิน',
            self::Coordinator => 'ผู้ประสานงานมหาวิทยาลัย',
        };
    }
}
