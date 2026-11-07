<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArraySheet;
use App\Exports\Workbook;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\ParticipantType;
use App\Models\RegistrationGroup;
use App\Models\Setting;
use App\Support\Images;
use App\Support\ThaiId;
use Barryvdh\DomPDF\Facade\Pdf;
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
        'board_game' => 'รายชื่อทีมแข่งขันบอร์ดเกม',
        'badges' => 'รูปป้ายชื่อ (ZIP + CSV สำหรับ mail-merge)',
        'badge_pdf' => 'ป้ายชื่อพร้อมพิมพ์ (PDF)',
    ];

    /** Badge page width; the height follows the background's aspect ratio. */
    private const BADGE_WIDTH_MM = 100;

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

        if ($type === 'badge_pdf') {
            return $this->badgePdf();
        }

        $sheets = match ($type) {
            'participants' => [$this->participantsSheet($withId)],
            'receipts' => [$this->receiptsSheet($withId)],
            'shirts' => $this->shirtSheets(),
            'food' => $this->foodSheets(),
            'finance' => [$this->financeSheet()],
            'checkins' => [$this->checkinSheet()],
            'board_game' => $this->boardGameSheets(),
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
            $p->attendance->label(), $p->title, $p->full_name, $p->mobile_phone,
            $withId ? ThaiId::format($p->national_id) : $p->maskedNationalId(),
            $p->food_type ?? '-', $p->food_allergy, $p->shirtSizeLabel(), (float) $p->fee_amount,
            $p->group->payment_status->label(), $p->badge_photo_path ? 'มี' : '-',
        ])->all();

        return new ArraySheet('ผู้เข้าร่วม', [
            'ลำดับ', 'รหัส', 'มหาวิทยาลัย', 'ส่วน', 'ประเภท', 'ตำแหน่ง', 'รูปแบบการลงทะเบียน', 'คำนำหน้า', 'ชื่อ-นามสกุล', 'โทรศัพท์มือถือ',
            'เลขประจำตัวประชาชน', 'ประเภทอาหาร', 'อาหารที่แพ้', 'ไซซ์เสื้อ', 'ค่าลงทะเบียน', 'สถานะชำระเงิน', 'รูปป้ายชื่อ',
        ], $rows);
    }

    private function receiptsSheet(bool $withId): ArraySheet
    {
        // Board-game-only entrants pay nothing, so they get no receipt.
        $rows = $this->participants()->filter(fn (Participant $p) => $p->attendsConference())->values()->map(fn (Participant $p, $i) => [
            $i + 1, $p->code, $p->displayName(), $p->receiptName(), $p->receiptAddress(),
            $withId ? ThaiId::format($p->receiptTaxId()) : ThaiId::mask($p->receiptTaxId()),
            $p->group->university->name, $p->type->name_th, (float) $p->fee_amount,
            $p->group->payment_method?->label(), $p->group->payment_status->label(),
            $p->group->paidAt()?->format('d/m/Y H:i'), $p->receipt_no,
        ])->all();

        return new ArraySheet('ข้อมูลใบเสร็จ', [
            'ลำดับ', 'รหัส', 'ชื่อ-นามสกุล', 'ชื่อผู้รับใบเสร็จ', 'ที่อยู่', 'เลขประจำตัวผู้เสียภาษี', 'มหาวิทยาลัย', 'ประเภท', 'จำนวนเงิน',
            'ช่องทาง', 'สถานะ', 'วันที่ชำระ/อนุมัติ', 'เลขที่ใบเสร็จ',
        ], $rows);
    }

    private function shirtSheets(): array
    {
        $all = $this->participants()->filter(fn (Participant $p) => $p->shirt_size !== null)->values();
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
        $all = $this->participants()->filter(fn (Participant $p) => $p->food_type !== null)->values();

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

    /**
     * Board game teams: a per-university count against the team limits, and everyone on a team.
     *
     * @return list<ArraySheet>
     */
    private function boardGameSheets(): array
    {
        $entrants = $this->participants()->filter(fn (Participant $p) => $p->attendance->includesBoardGame())->values();
        $countOf = fn (Collection $ps, string $code) => $ps->filter(fn (Participant $p) => $p->type->code === $code)->count();

        $teams = $entrants->groupBy(fn (Participant $p) => $p->group->university->name)->map(fn (Collection $ps, string $name) => [
            $name, $countOf($ps, ParticipantType::STAFF), $countOf($ps, ParticipantType::STUDENT), $ps->count(),
        ])->values()->all();
        $teams[] = ['รวม', $countOf($entrants, ParticipantType::STAFF), $countOf($entrants, ParticipantType::STUDENT), $entrants->count()];

        $limits = Participant::BOARD_GAME_LIMITS;

        return [
            new ArraySheet('สรุปทีม', [
                'มหาวิทยาลัย',
                'บุคลากร (สูงสุด '.$limits[ParticipantType::STAFF].')',
                'นักศึกษา (สูงสุด '.$limits[ParticipantType::STUDENT].')',
                'รวม',
            ], $teams),
            new ArraySheet('รายชื่อ', [
                'ลำดับ', 'รหัส', 'มหาวิทยาลัย', 'ตำแหน่ง', 'ชื่อ-นามสกุล', 'โทรศัพท์มือถือ', 'รูปแบบการลงทะเบียน', 'สถานะชำระเงิน', 'เช็กอิน',
            ], $entrants->map(fn (Participant $p, int $i) => [
                $i + 1, $p->code, $p->group->university->name, $p->position, $p->displayName(), $p->mobile_phone,
                $p->attendance->label(), $p->group->payment_status->label(),
                $p->checked_in_at?->format('d/m/Y H:i') ?? 'ยังไม่เช็กอิน',
            ])->all()),
        ];
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

    /** One badge per page: uploaded background, photo cropped into the frame, name/position/university below. */
    private function badgePdf()
    {
        $disk = Storage::disk('local');
        $background = Setting::get('badge_background_path');
        abort_unless($background && $disk->exists($background), 422, 'กรุณาอัปโหลดพื้นหลังป้ายชื่อที่หน้าตั้งค่าก่อน');

        [$imageWidth, $imageHeight] = getimagesizefromstring($disk->get($background));
        $pageWidth = self::BADGE_WIDTH_MM / 25.4 * 72;
        $pageHeight = $pageWidth * $imageHeight / $imageWidth;
        $percent = fn (string $key, float $of) => round((float) Setting::get($key) / 100 * $of, 2);

        $box = [
            'left' => $percent('badge_photo_left', $pageWidth),
            'top' => $percent('badge_photo_top', $pageHeight),
            'width' => $percent('badge_photo_width', $pageWidth),
            'height' => $percent('badge_photo_height', $pageHeight),
        ];
        $photoWidth = 600;
        $photoHeight = (int) round($photoWidth * $box['height'] / max($box['width'], 1));

        $badges = $this->participants()->map(fn (Participant $p) => [
            'name' => $p->displayName(),
            'position' => $p->position,
            'university' => $p->group->university->name,
            'photo' => $p->badge_photo_path && $disk->exists($p->badge_photo_path)
                ? 'data:image/jpeg;base64,'.base64_encode(Images::cover($disk->path($p->badge_photo_path), $photoWidth, $photoHeight))
                : null,
        ]);

        $pdf = Pdf::loadView('pdf.badges', [
            'badges' => $badges,
            'background' => 'data:image/jpeg;base64,'.base64_encode($disk->get($background)),
            'page' => ['width' => round($pageWidth, 2), 'height' => round($pageHeight, 2)],
            'box' => $box,
            'textTop' => $percent('badge_text_top', $pageHeight),
        ])->setPaper([0, 0, $pageWidth, $pageHeight]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rsc20-badges-'.now()->format('Ymd-Hi').'.pdf"',
        ]);
    }
}
