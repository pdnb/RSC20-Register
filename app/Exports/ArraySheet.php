<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** One worksheet built from headings + rows; used by every Excel download. */
class ArraySheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  list<string>  $headings
     * @param  list<array<int, mixed>>  $rows
     */
    public function __construct(
        private string $title,
        private array $headings,
        private array $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFill()
            ->setFillType('solid')->getStartColor()->setRGB('DBEAFE');
        $sheet->freezePane('A2');

        return [1 => ['font' => ['bold' => true]]];
    }
}
