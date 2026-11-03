<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        @include('pdf.styles')
        @page { margin: 30px 40px; }
        .card { border: 1px dashed #64748b; padding: 8px 14px; margin-bottom: 10px; page-break-inside: avoid; }
        .card td { border: none; padding: 0 4px; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 11pt; }
    </style>
</head>
<body>
    <h2 style="margin: 0 0 8px; font-size: 20pt;">ใบแจ้งบัญชีผู้ประสานงาน · ระบบลงทะเบียนสัมมนาสมาพันธ์นักศึกษามหาวิทยาลัยราชภัฏแห่งประเทศไทย ครั้งที่ 20</h2>
    @foreach ($rows as $row)
        <div class="card">
            <table>
                <tr><td style="width: 30%">มหาวิทยาลัย</td><td><b>{{ $row['university'] }}</b></td></tr>
                <tr><td>เข้าสู่ระบบที่</td><td>{{ $loginUrl }}</td></tr>
                <tr><td>ชื่อผู้ใช้ (username)</td><td class="mono">{{ $row['username'] }}</td></tr>
                <tr><td>รหัสผ่านชั่วคราว</td><td class="mono">{{ $row['password'] }}</td></tr>
                <tr><td colspan="2" class="muted">ระบบจะให้เปลี่ยนรหัสผ่านเมื่อเข้าสู่ระบบครั้งแรก</td></tr>
            </table>
        </div>
    @endforeach
</body>
</html>
