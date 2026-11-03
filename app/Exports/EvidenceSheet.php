<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/** "หลักฐานการโอน" sheet: one row per slip with its thumbnail embedded in column F. */
class EvidenceSheet implements FromArray, WithColumnWidths, WithDrawings, WithEvents, WithTitle
{
    private const ROW_HEIGHT = 240; // points

    /**
     * @param  list<array{no: int, university: string, reference: ?string, transferred_at: ?string, amount: float, people: string, image: ?string}>  $items
     */
    public function __construct(private array $items) {}

    public function array(): array
    {
        $rows = [['หลักฐาน #', 'มหาวิทยาลัย', 'วันเวลาที่โอน / รับเงิน', 'เลขอ้างอิง', 'จำนวนเงิน', 'สลิป', 'ผู้เข้าร่วมที่ครอบคลุม']];

        foreach ($this->items as $item) {
            $rows[] = [$item['no'], $item['university'], $item['transferred_at'], $item['reference'], $item['amount'], $item['image'] ? '' : 'ชำระหน้างาน', $item['people']];
        }

        return $rows;
    }

    public function drawings(): array
    {
        $drawings = [];

        foreach ($this->items as $i => $item) {
            if (! $item['image']) {
                continue;
            }
            $drawing = new Drawing;
            $drawing->setName('slip-'.$item['no']);
            $drawing->setPath($item['image']);
            $drawing->setHeight(self::ROW_HEIGHT * 4 / 3 - 10); // points → px
            $drawing->setCoordinates('F'.($i + 2));
            $drawing->setOffsetX(4)->setOffsetY(4);
            $drawings[] = $drawing;
        }

        return $drawings;
    }

    public function columnWidths(): array
    {
        return ['A' => 10, 'B' => 32, 'C' => 20, 'D' => 18, 'E' => 14, 'F' => 32, 'G' => 50];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $sheet->getStyle('A1:G1')->getFont()->setBold(true);
            foreach (array_keys($this->items) as $i) {
                $sheet->getRowDimension($i + 2)->setRowHeight(self::ROW_HEIGHT);
            }
            $sheet->getStyle('A2:G'.(count($this->items) + 1))->getAlignment()->setVertical('top')->setWrapText(true);
        }];
    }

    public function title(): string
    {
        return 'หลักฐานการโอน';
    }
}
