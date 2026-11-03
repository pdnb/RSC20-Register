<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArraySheet;
use App\Exports\EvidenceSheet;
use App\Exports\Workbook;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FinanceSubmission;
use App\Models\Setting;
use App\Services\FinanceReport;
use App\Support\ThaiId;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class FinanceReportController extends Controller
{
    public function download(FinanceSubmission $submission, string $format)
    {
        AuditLog::record('finance.downloaded', $submission, ['format' => $format]);
        $data = FinanceReport::data($submission);

        return match ($format) {
            'pdf' => $this->pdf($data),
            'xlsx' => $this->excel($data),
            'zip' => $this->zip($data),
        };
    }

    private function pdf(array $data)
    {
        $temp = [];
        $data['slipImages'] = $data['evidence']->mapWithKeys(function ($e) use (&$temp) {
            $file = $e['slip_path'] ? FinanceReport::slipThumbnail($e['slip_path'], 1100) : null;
            if ($file) {
                $temp[] = $file;
            }

            return [$e['no'] => $file ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($file)) : null];
        });

        $pdf = Pdf::loadView('pdf.finance-report', $data + [
            'office' => Setting::get('finance_office_name'),
            'sender' => Setting::get('finance_sender_name'),
            'eventName' => Setting::get('event_name'),
        ])->setPaper('a4', 'landscape');

        $output = $pdf->output();
        array_map('unlink', $temp);

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$data['submission']->submission_no.'.pdf"',
        ]);
    }

    private function excel(array $data)
    {
        $s = $data['submission'];

        $rows = $data['participants']->map(fn ($p, $i) => [
            $i + 1, $p->code, $p->receipt_name, $p->receipt_address, ThaiId::format($p->receipt_tax_id),
            $p->group->university->name, $p->type->name_th, (float) $p->fee_amount, $p->group->payment_method?->label(),
            $p->group->paidAt()?->format('d/m/Y H:i'), $p->group->slip_reference,
            'หลักฐาน #'.$data['evidenceNo'][$p->registration_group_id], $p->receipt_no ?? '',
        ])->all();
        $rows[] = ['', '', '', '', '', '', 'รวมทั้งสิ้น', $data['total'], '', '', '', '', ''];
        $rows[] = ['', '', '('.$data['totalText'].')'];

        $summary = $data['summary']->map(fn ($r) => [$r['type'], $r['method'], $r['count'], $r['amount']])->all();
        $summary[] = ['รวมทั้งสิ้น', '', $data['participants']->count(), $data['total']];

        $temp = [];
        $evidence = $data['evidence']->map(function ($e) use (&$temp) {
            $image = $e['slip_path'] ? FinanceReport::slipThumbnail($e['slip_path'], 420) : null;
            if ($image) {
                $temp[] = $image;
            }
            $g = $e['group'];

            return [
                'no' => $e['no'],
                'university' => $g->university->name,
                'reference' => $g->slip_reference,
                'transferred_at' => ($e['is_transfer'] ? $g->transferred_at : $g->onsite_received_at)?->format('d/m/Y H:i'),
                'amount' => $e['amount'],
                'people' => $e['participants']->map(fn ($p) => $p->code.' '.$p->displayName())->join("\n"),
                'image' => $image,
            ];
        })->all();

        $file = $s->submission_no.'.xlsx';
        Excel::store(new Workbook([
            new ArraySheet('รายการออกใบเสร็จ', [
                'ลำดับ', 'รหัสผู้ลงทะเบียน', 'ชื่อผู้รับใบเสร็จ', 'ที่อยู่', 'เลขประจำตัวผู้เสียภาษี', 'มหาวิทยาลัย', 'ประเภท',
                'จำนวนเงิน', 'ช่องทาง', 'วันที่ชำระ/อนุมัติ', 'เลขอ้างอิงสลิป', 'อ้างอิงหลักฐาน', 'เลขที่ใบเสร็จ (กองคลังกรอก)',
            ], $rows),
            new ArraySheet('สรุป', ['ประเภท', 'ช่องทาง', 'จำนวนคน', 'จำนวนเงิน'], $summary),
            new EvidenceSheet($evidence),
        ]), 'finance/'.$file, 'local');
        array_map('unlink', $temp);

        return Storage::disk('local')->download('finance/'.$file, $file);
    }

    private function zip(array $data)
    {
        $s = $data['submission'];
        $path = tempnam(sys_get_temp_dir(), 'slips');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($data['evidence'] as $e) {
            if ($e['slip_path'] && Storage::disk('local')->exists($e['slip_path'])) {
                $name = sprintf('%s_%02d_%s_%s.jpg', $s->submission_no, $e['no'], $e['group']->university->name, number_format($e['amount'], 2, '.', ''));
                $zip->addFromString($name, Storage::disk('local')->get($e['slip_path']));
            }
        }

        if ($zip->numFiles === 0) {
            $zip->addFromString('README.txt', 'รายงานนี้มีเฉพาะรายการชำระหน้างาน จึงไม่มีสลิป');
        }
        $zip->close();

        return response()->download($path, $s->submission_no.'-slips.zip')->deleteFileAfterSend();
    }
}
