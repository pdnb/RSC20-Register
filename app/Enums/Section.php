<?php

namespace App\Enums;

enum Section: string
{
    case Executive = 'executive';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Executive => 'ข้อมูลผู้บริหาร',
            self::Member => 'ข้อมูลบุคลากร/นักศึกษา',
        };
    }
}
