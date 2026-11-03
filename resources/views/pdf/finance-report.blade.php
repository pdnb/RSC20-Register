<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        @include('pdf.styles')
        @page { margin: 28px 30px 36px 30px; }
        .head { text-align: center; margin-bottom: 6px; }
        .head h1 { font-size: 22pt; margin: 0; }
        .meta td { border: none; padding: 0 4px; }
        .rows td, .rows th { font-size: 14pt; }
        .sign { width: 100%; margin-top: 24px; }
        .sign td { border: none; text-align: center; width: 50%; padding-top: 18px; }
        .slip { text-align: center; }
        .slip img { max-width: 100%; max-height: 470px; border: 1px solid #94a3b8; }
        #footer { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 12pt; color: #64748b; }
    </style>
</head>
<body>
    <div id="footer">{{ $submission->submission_no }} · พิมพ์เมื่อ {{ now()->format('d/m/Y H:i') }}</div>

    <div class="head">
        <h1>บันทึกนำส่งรายการรับเงินค่าลงทะเบียน เพื่อออกใบเสร็จรับเงิน</h1>
        <div>{{ $eventName }}</div>
    </div>

    <table class="meta">
        <tr>
            <td>เลขที่นำส่ง <b>{{ $submission->submission_no }}</b></td>
            <td class="right">วันที่ {{ $submission->created_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>เรียน {{ $office }}</td>
            <td class="right">ช่วงวันที่ชำระ/อนุมัติ {{ $submission->date_from?->format('d/m/Y') ?? 'ทั้งหมด' }} – {{ $submission->date_to?->format('d/m/Y') ?? $submission->created_at->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td colspan="2">ขอนำส่งรายการผู้ลงทะเบียน จำนวน {{ number_format($participants->count()) }} ราย รวมเป็นเงิน {{ number_format($total, 2) }} บาท ({{ $totalText }}) เพื่อออกใบเสร็จรับเงินในนามบุคคลตามรายละเอียดดังนี้</td>
        </tr>
    </table>

    <table class="rows" style="margin-top: 6px;">
        <thead>
            <tr>
                <th style="width: 3%">ที่</th>
                <th style="width: 8%">รหัส</th>
                <th style="width: 10%">ชื่อ-นามสกุล</th>
                <th style="width: 13%">ชื่อผู้รับใบเสร็จ</th>
                <th style="width: 14%">ที่อยู่</th>
                <th style="width: 11%">เลขประจำตัวผู้เสียภาษี</th>
                <th style="width: 12%">มหาวิทยาลัย</th>
                <th style="width: 6%">ประเภท</th>
                <th style="width: 7%">จำนวนเงิน</th>
                <th style="width: 7%">ช่องทาง / วันที่</th>
                <th style="width: 4%">หลักฐาน</th>
                <th style="width: 7%">เลขที่ใบเสร็จ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($participants as $i => $p)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $p->code }}</td>
                    <td>{{ $p->displayName() }}</td>
                    <td>{{ $p->receiptName() }}</td>
                    <td>{{ $p->receiptAddress() }}</td>
                    <td>{{ \App\Support\ThaiId::format($p->receiptTaxId()) }}</td>
                    <td>{{ $p->group->university->name }}</td>
                    <td>{{ $p->type->name_th }}</td>
                    <td class="right">{{ number_format($p->fee_amount, 2) }}</td>
                    <td>{{ $p->group->payment_method?->label() }}<br><span class="muted">{{ $p->group->paidAt()?->format('d/m/Y') ?? 'รอชำระหน้างาน' }}</span></td>
                    <td class="center">#{{ $evidenceNo[$p->registration_group_id] }}</td>
                    <td></td>
                </tr>
            @endforeach
            <tr>
                <td colspan="8" class="right"><b>รวมทั้งสิ้น</b></td>
                <td class="right"><b>{{ number_format($total, 2) }}</b></td>
                <td colspan="3">({{ $totalText }})</td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top: 10px; width: 60%;">
        <thead><tr><th>ประเภท</th><th>ช่องทาง</th><th>จำนวน (ราย)</th><th>จำนวนเงิน (บาท)</th></tr></thead>
        <tbody>
            @foreach ($summary as $row)
                <tr><td>{{ $row['type'] }}</td><td>{{ $row['method'] }}</td><td class="right">{{ $row['count'] }}</td><td class="right">{{ number_format($row['amount'], 2) }}</td></tr>
            @endforeach
            <tr><td colspan="2"><b>รวม</b></td><td class="right"><b>{{ $participants->count() }}</b></td><td class="right"><b>{{ number_format($total, 2) }}</b></td></tr>
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td>ลงชื่อ ........................................ ผู้นำส่ง<br>({{ $sender ?: '........................................' }})<br>วันที่ ......../......../........</td>
            <td>ลงชื่อ ........................................ ผู้รับ<br>(........................................)<br>{{ $office }}<br>วันที่ ......../......../........</td>
        </tr>
    </table>

    {{-- Appendix: one page per slip --}}
    @foreach ($evidence as $e)
        <div class="page-break">
            <h2 style="margin: 0; font-size: 20pt;">ภาคผนวก หลักฐานการชำระเงิน #{{ $e['no'] }}</h2>
            <table class="meta" style="margin-bottom: 6px;">
                <tr>
                    <td>มหาวิทยาลัย: <b>{{ $e['group']->university->name }}</b></td>
                    <td>จำนวนเงิน: <b>{{ number_format($e['amount'], 2) }} บาท</b></td>
                    @if ($e['is_transfer'])
                        <td>วันเวลาที่โอน: {{ $e['group']->transferred_at?->format('d/m/Y H:i') }}</td>
                        <td>เลขอ้างอิง: {{ $e['group']->slip_reference ?: '-' }}</td>
                    @else
                        <td colspan="2">ชำระหน้างาน รับโดย {{ $e['group']->onsiteReceiver?->name }} เวลา {{ $e['group']->onsite_received_at?->format('d/m/Y H:i') }}</td>
                    @endif
                </tr>
                <tr>
                    <td colspan="4" class="muted">ครอบคลุม {{ $e['participants']->count() }} ราย: {{ $e['participants']->map(fn ($p) => $p->code.' '.$p->displayName())->join(', ') }}</td>
                </tr>
            </table>
            @if ($e['is_transfer'])
                <div class="slip">
                    @if ($slipImages[$e['no']])
                        <img src="{{ $slipImages[$e['no']] }}" alt="slip">
                    @else
                        <p class="muted">ไม่พบไฟล์สลิป</p>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</body>
</html>
