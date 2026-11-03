# ระบบลงทะเบียนสัมมนาสมาพันธ์นักศึกษามหาวิทยาลัยราชภัฏแห่งประเทศไทย ครั้งที่ 20

จัดโดยมหาวิทยาลัยราชภัฏสุราษฎร์ธานี · Laravel 13 + Livewire 4 + WireKit UI · ฟอนต์ Anuphan · โทนน้ำเงิน/เทา/ขาว

## ผู้ใช้งาน
| บทบาท | เข้าสู่ระบบ | หน้าที่ |
|---|---|---|
| ผู้ประสานงานมหาวิทยาลัย | `/login` ด้วย username + รหัสผ่านที่ผู้ดูแลออกให้ (เปลี่ยนรหัสเมื่อเข้าครั้งแรก) | ลงทะเบียนส่วนที่ 1 ผู้บริหาร / ส่วนที่ 2 บุคลากร-นักศึกษา, แนบรูปป้ายชื่อ, ข้อมูลใบเสร็จ, แนบสลิปหรือเลือกจ่ายหน้างาน, QR |
| ผู้ดูแล (admin) | `/admin/login` ด้วย Auth0 (เฉพาะอีเมลใน allowlist) | ตรวจสลิป, ค่าลงทะเบียน, บัญชีผู้ประสานงาน, Export Excel, รายงานนำส่งกองคลัง |
| เจ้าหน้าที่เช็กอิน (staff) | `/admin/login` ด้วย Auth0 | สแกน QR / ค้นชื่อ, เช็กอิน, รับเงินหน้างาน |

## ติดตั้ง (dev บน Herd)
```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # มหาวิทยาลัยราชภัฏ 38 แห่ง + ประเภทผู้เข้าร่วม (+ บัญชีทดสอบเมื่อ APP_ENV=local)
herd isolate 8.5                    # WireKit ต้องใช้ PHP ≥ 8.4
php artisan admin:allow you@sru.ac.th --role=admin
```
บัญชีทดสอบ (local) อยู่ใน `storage/app/private/demo-credentials.txt`

### Auth0
สร้าง Regular Web Application แล้วกรอก `AUTH0_DOMAIN`, `AUTH0_CLIENT_ID`, `AUTH0_CLIENT_SECRET` ใน `.env`
- Allowed Callback URL: `{APP_URL}/admin/auth/auth0/callback`
- Allowed Logout URL: `{APP_URL}/admin/login`

### Production
- ใช้ MySQL (ดูตัวอย่างใน `.env.example`)
- **สำรอง `APP_KEY` ไว้** เพราะเลขประจำตัวประชาชนและเลขผู้เสียภาษีเข้ารหัสด้วยคีย์นี้ ถ้าคีย์หายจะถอดรหัสไม่ได้
- สลิปและรูปป้ายชื่อเก็บใน `storage/app/private` (ไม่เปิดสาธารณะ) ต้องรวมไว้ในการสำรองข้อมูลด้วย

## ค่าลงทะเบียน
ตั้งที่ `/admin/settings` แยกตามผู้บริหาร / บุคลากร / นักศึกษา ระบบบันทึกค่าของแต่ละคนไว้ ณ ตอนลงทะเบียน (`participants.fee_amount`) และคำนวณยอดกลุ่มใน `App\Services\FeeCalculator` ถ้ายอดเปลี่ยนหลังส่งสลิป สถานะจะกลับเป็น "รอชำระ"

## รายงานนำส่งกองคลัง
`/admin/finance` รวมเฉพาะผู้ที่ชำระแล้ว (สลิปผ่านการอนุมัติ หรือรับเงินหน้างานแล้ว) หนึ่งแถวต่อหนึ่งคน และนำส่งซ้ำไม่ได้ ดาวน์โหลดได้ 3 แบบ
- PDF A4 แนวนอน (THSarabunNew) มีช่องลงนาม และภาคผนวกรูปสลิปหน้าละ 1 รายการ
- Excel มีชีตรายการ / สรุป / หลักฐานการโอน (ฝังรูปสลิป)
- ZIP รวมสลิปต้นฉบับ

## ทดสอบ
```bash
php artisan test
vendor/bin/pint
```
