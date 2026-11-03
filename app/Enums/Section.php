<?php

namespace App\Enums;

enum Section: string
{
    case Executive = 'executive';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Executive => 'ส่วนที่ 1 ข้อมูลผู้บริหาร',
            self::Member => 'ส่วนที่ 2 ข้อมูลบุคลากร/นักศึกษา',
        };
    }
}
