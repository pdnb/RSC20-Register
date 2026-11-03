<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArraySheet;
use App\Exports\Workbook;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\RegistrationGroup;
use App\Support\ThaiId;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class ExportController extends Controller
{
    public const TYPES = [
        'participants' => 'รายชื่อผู้เข้าร่วมทั้งหมด',
        'receipts' => 'ข้อมูลสำหรับออกใบเสร็จ (รายบุคคล)',
        'shirts' => 'สรุปไซซ์เสื้อ',
        'food' => 'สรุปอาหารและอาหารที่แพ้',
        'finance' => 'สรุปการเงินรายมหาวิทยาลัย',
        'checkins' => 'รายชื่อการเช็กอิน',
        'badges' => 'รูปป้ายชื่อ (ZIP + CSV สำหรับ mail-merge)',
    ];

    public function index()
    {
        return view('admin.exports', ['types' => self::TYPES]);
    }

    public function download(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $withId = $request->boolean('with_id') && in_array($type, ['participants', 'receipts'], true);
        AuditLog::record('export.'.$type, null, ['with_national_id' => $withId]);

        if ($type === 'badges') {
            return $this->badges();
        }

        $sheets = match ($type) {
            'participants' => [$this->participantsSheet($withId)],
            'receipts' => [$this->receiptsSheet($withId)],
            'shirts' => $this->shirtSheets(),
            'food' => $this->foodSheets(),
            'finance' => [$this->financeSheet()],
            'checkins' => [$this->checkinSheet()],
        };

        return Excel::download(new Workbook($sheets), 'rsc20-'.$type.'-'.now()->format('Ymd-Hi').'.xlsx');
    }

    /** @return Collection<int, Participant> */
    private function participants(): Collection
    {
        return Participant::query()
            ->with(['group.university', 'type', 'checkedInBy'])
            ->get()
            ->sortBy([fn ($a, $b) => $a->group->university->sort_order <=> $b->group->university->sort_order, ['section', 'asc'], ['id', 'asc']])
            ->values();
    }

    private function participantsSheet(bool $withId): ArraySheet
    {
        $rows = $this->participants()->map(fn (Participant $p, $i) => [
            $i + 1, $p->code, $p->group->university->name, $p->section->label(), $p->type->name_th, $p->position,
            $p->title, $p->full_name, $p->mobile_phone,
            $withId ? ThaiId::format($p->national_id) : $p->maskedNationalId(),
            $p->food_type, $p->food_allergy, $p->shirtSizeLabel(), (float) $p->fee_amount,
            $p->group->payment_status->label(), $p->badge_photo_path ? 'มี' : '-',
        ])->all();

        return new ArraySheet('ผู้เข้าร่วม', [
            'ลำดับ', 'รหัส', 'มหาวิทยาลัย', 'ส่วน', 'ประเภท', 'ตำแหน่ง', 'คำนำหน้า', 'ชื่อ-นามสกุล', 'โทรศัพท์มือถือ',
            'เลขประจำตัวประชาชน', 'ประเภทอาหาร', 'อาหารที่แพ้', 'ไซซ์เสื้อ', 'ค่าลงทะเบียน', 'สถานะชำระเงิน', 'รูปป้ายชื่อ',
        ], $rows);
    }

    private function receiptsSheet(bool $withId): ArraySheet
    {
        $rows = $this->participants()->map(fn (Participant $p, $i) => [
            $i + 1, $p->code, $p->receiptName(), $p->receiptAddress(),
            $withId ? ThaiId::format($p->receiptTaxId()) : ThaiId::mask($p->receiptTaxId()),
            $p->group->university->name, $p->type->name_th, (float) $p->fee_amount,
            $p->group->payment_method?->label(), $p->group->payment_status->label(),
            $p->group->paidAt()?->format('d/m/Y H:i'), $p->receipt_no,
        ])->all();

        return new ArraySheet('ข้อมูลใบเสร็จ', [
            'ลำดับ', 'รหัส', 'ชื่อผู้รับใบเสร็จ', 'ที่อยู่', 'เลขประจำตัวผู้เสียภาษี', 'มหาวิทยาลัย', 'ประเภท', 'จำนวนเงิน',
            'ช่องทาง', 'สถานะ', 'วันที่ชำระ/อนุมัติ', 'เลขที่ใบเสร็จ',
        ], $rows);
    }

    private function shirtSheets(): array
    {
        $all = $this->participants();
        $sizes = array_keys(Participant::SHIRT_SIZES);

        $byUniversity = $all->groupBy(fn ($p) => $p->group->university->name)->map(fn ($ps, $name) => [
            $name, ...array_map(fn ($s) => $ps->where('shirt_size', $s)->count(), $sizes), $ps->count(),
        ])->values()->all();
        $byUniversity[] = ['รวม', ...array_map(fn ($s) => $all->where('shirt_size', $s)->count(), $sizes), $all->count()];

        return [
            new ArraySheet('สรุปไซซ์เสื้อ', ['มหาวิทยาลัย', ...array_values(Participant::SHIRT_SIZES), 'รวม'], $byUniversity),
            new ArraySheet('รายชื่อ', ['รหัส', 'ชื่อ-นามสกุล', 'มหาวิทยาลัย', 'ไซซ์เสื้อ'],
                $all->map(fn ($p) => [$p->code, $p->displayName(), $p->group->university->name, $p->shirtSizeLabel()])->all()),
        ];
    }

    private function foodSheets(): array
    {
        $all = $this->participants();

        return [
            new ArraySheet('สรุปประเภทอาหาร', ['ประเภทอาหาร', 'จำนวน'],
                $all->countBy('food_type')->map(fn ($c, $t) => [$t, $c])->values()->all()),
            new ArraySheet('อาหารที่แพ้', ['รหัส', 'ชื่อ-นามสกุล', 'มหาวิทยาลัย', 'ประเภทอาหาร', 'อาหารที่แพ้', 'โทรศัพท์'],
                $all->filter(fn ($p) => $p->food_allergy)->map(fn ($p) => [
                    $p->code, $p->displayName(), $p->group->university->name, $p->food_type, $p->food_allergy, $p->mobile_phone,
                ])->values()->all()),
        ];
    }

    private function financeSheet(): ArraySheet
    {
        $rows = RegistrationGroup::query()->with('university')->withCount('participants')->get()
            ->sortBy('university.sort_order')
            ->map(fn (RegistrationGroup $g) => [
                $g->university->name, $g->participants_count, (float) $g->total_amount, $g->payment_method?->label(),
                $g->payment_status->label(), $g->paidAt()?->format('d/m/Y H:i'), $g->slip_reference,
            ])->values()->all();

        return new ArraySheet('สรุปการเงิน', ['มหาวิทยาลัย', 'จำนวนคน', 'ยอดเงิน', 'ช่องทาง', 'สถานะ', 'วันที่ชำระ/อนุมัติ', 'เลขอ้างอิงสลิป'], $rows);
    }

    private function checkinSheet(): ArraySheet
    {
        $rows = $this->participants()->map(fn (Participant $p) => [
            $p->code, $p->displayName(), $p->group->university->name, $p->type->name_th,
            $p->checked_in_at?->format('d/m/Y H:i') ?? 'ยังไม่เช็กอิน', $p->checkedInBy?->name,
        ])->all();

        return new ArraySheet('การเช็กอิน', ['รหัส', 'ชื่อ-นามสกุล', 'มหาวิทยาลัย', 'ประเภท', 'เวลาเช็กอิน', 'ผู้บันทึก'], $rows);
    }

    private function badges()
    {
        $path = tempnam(sys_get_temp_dir(), 'badges');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $csv = fopen('php://temp', 'w+');
        fwrite($csv, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel/Word read Thai correctly
        fputcsv($csv, ['code', 'title', 'full_name', 'display_name', 'position', 'type', 'university', 'photo_file']);

        foreach ($this->participants() as $p) {
            $file = $p->badge_photo_path && Storage::disk('local')->exists($p->badge_photo_path) ? 'photos/'.$p->code.'.jpg' : '';
            if ($file) {
                $zip->addFromString($file, Storage::disk('local')->get($p->badge_photo_path));
            }
            fputcsv($csv, [$p->code, $p->title, $p->full_name, $p->displayName(), $p->position, $p->type->name_th, $p->group->university->name, $file]);
        }

        rewind($csv);
        $zip->addFromString('badges.csv', stream_get_contents($csv));
        $zip->close();

        return response()->download($path, 'rsc20-badges-'.now()->format('Ymd-Hi').'.zip')->deleteFileAfterSend();
    }
}
