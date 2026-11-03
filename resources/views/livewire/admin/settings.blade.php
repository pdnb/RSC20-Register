<form wire:submit="save" class="max-w-3xl space-y-4">
    <x-wirekit::card>
        <x-wirekit::card.header><h2 class="font-semibold">ค่าลงทะเบียนตามประเภทผู้เข้าร่วม (บาท/คน)</h2></x-wirekit::card.header>
        <x-wirekit::card.body class="space-y-3">
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ($types as $type)
                    <x-wirekit::input wire:model="fees.{{ $type->id }}" type="number" step="0.01" min="0" :label="$type->name_th" suffix="บาท" required />
                @endforeach
            </div>
            <p class="text-xs text-slate-500">ระบบบันทึกค่าลงทะเบียนของแต่ละคนไว้ ณ ตอนลงทะเบียน การแก้ค่าที่นี่จึงไม่เปลี่ยนยอดของรายชื่อที่มีอยู่แล้ว</p>
        </x-wirekit::card.body>
    </x-wirekit::card>

    <x-wirekit::card>
        <x-wirekit::card.header><h2 class="font-semibold">งานและช่วงเวลารับลงทะเบียน</h2></x-wirekit::card.header>
        <x-wirekit::card.body class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-wirekit::input wire:model="settings.event_name" label="ชื่องาน" /></div>
            <x-wirekit::input wire:model="settings.event_dates" label="วันที่จัดงาน" placeholder="เช่น 15–17 มกราคม 2570" />
            <x-wirekit::input wire:model="settings.event_venue" label="สถานที่" />
            <x-wirekit::input wire:model="settings.registration_opens_at" type="datetime-local" label="เปิดรับลงทะเบียน" hint="เว้นว่าง = เปิดทันที" />
            <x-wirekit::input wire:model="settings.registration_closes_at" type="datetime-local" label="ปิดรับลงทะเบียน" hint="เว้นว่าง = ไม่มีกำหนดปิด" />
        </x-wirekit::card.body>
    </x-wirekit::card>

    <x-wirekit::card>
        <x-wirekit::card.header><h2 class="font-semibold">บัญชีรับโอน</h2></x-wirekit::card.header>
        <x-wirekit::card.body class="grid gap-4 sm:grid-cols-3">
            <x-wirekit::input wire:model="settings.bank_name" label="ธนาคาร" />
            <x-wirekit::input wire:model="settings.bank_account_no" label="เลขที่บัญชี" mono />
            <x-wirekit::input wire:model="settings.bank_account_name" label="ชื่อบัญชี" />
        </x-wirekit::card.body>
    </x-wirekit::card>

    <x-wirekit::card>
        <x-wirekit::card.header><h2 class="font-semibold">รายงานนำส่งกองคลัง และ PDPA</h2></x-wirekit::card.header>
        <x-wirekit::card.body class="grid gap-4 sm:grid-cols-2">
            <x-wirekit::input wire:model="settings.finance_office_name" label="เรียน (หน่วยงานรับ)" />
            <x-wirekit::input wire:model="settings.finance_sender_name" label="ชื่อผู้นำส่ง (ในช่องลงนาม)" />
            <div class="sm:col-span-2"><x-wirekit::textarea wire:model="settings.pdpa_text" label="ข้อความขอความยินยอม (PDPA)" rows="4" /></div>
        </x-wirekit::card.body>
    </x-wirekit::card>

    <div class="flex justify-end">
        <x-wirekit::button type="submit">บันทึกการตั้งค่า</x-wirekit::button>
    </div>
</form>
