<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Workbook implements Export, WithMultipleSheets
{
    /** @param  list<object>  $sheets */
    public function __construct(private array $sheets) {}

    public function sheets(): array
    {
        return $this->sheets;
    }
}
