<form wire:submit="save" class="space-y-4">
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

    <x-wirekit::card>
        <x-wirekit::card.header><h2 class="font-semibold">ป้ายชื่อ</h2></x-wirekit::card.header>
        <x-wirekit::card.body class="grid gap-6 md:grid-cols-[16rem_1fr]">
            <div>
                @if ($badgeBackgroundUrl)
                    <div class="relative overflow-hidden rounded-lg border border-slate-200">
                        <img src="{{ $badgeBackgroundUrl }}" class="block w-full" alt="พื้นหลังป้ายชื่อ">
                        <div class="absolute border-2 border-dashed border-red-500 bg-red-500/15"
                            style="left: {{ (float) $settings['badge_photo_left'] }}%; top: {{ (float) $settings['badge_photo_top'] }}%; width: {{ (float) $settings['badge_photo_width'] }}%; height: {{ (float) $settings['badge_photo_height'] }}%"></div>
                        <div class="absolute inset-x-[8%] border-t-2 border-dashed border-blue-600 pt-1 text-center text-[10px] font-semibold text-blue-700"
                            style="top: {{ (float) $settings['badge_text_top'] }}%">ชื่อ · ตำแหน่ง · มหาวิทยาลัย</div>
                    </div>
                    <div class="mt-2 flex justify-end">
                        <x-wirekit::button size="sm" intent="danger" surface="ghost" wire:click="removeBadgeBackground" wire:confirm="ลบพื้นหลังป้ายชื่อ?">ลบพื้นหลัง</x-wirekit::button>
                    </div>
                @else
                    <div class="flex aspect-[2/3] items-center justify-center rounded-lg border border-dashed border-slate-300 text-sm text-slate-400">ยังไม่มีพื้นหลัง</div>
                @endif
            </div>

            <div class="space-y-4">
                <div>
                    <x-wirekit::file-upload wire:model="badgeBackground" accept="image/jpeg,image/png"
                        :label="$badgeBackgroundUrl ? 'เปลี่ยนพื้นหลังป้ายชื่อ' : 'อัปโหลดพื้นหลังป้ายชื่อ'"
                        hint="JPG หรือ PNG แนวตั้ง ไม่เกิน 10 MB — ใช้ขนาดหน้ากระดาษตามสัดส่วนของภาพ"
                        :error="$errors->first('badgeBackground')" />
                    <div wire:loading wire:target="badgeBackground" class="mt-1 text-xs text-slate-500">กำลังอัปโหลด…</div>
                </div>

                <div>
                    <div class="mb-2 text-sm font-medium text-slate-700">ตำแหน่งกรอบรูป (% ของภาพพื้นหลัง)</div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_photo_left" type="number" step="0.1" min="0" max="100" label="ซ้าย" suffix="%" :error="$errors->first('settings.badge_photo_left')" />
                        <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_photo_top" type="number" step="0.1" min="0" max="100" label="บน" suffix="%" :error="$errors->first('settings.badge_photo_top')" />
                        <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_photo_width" type="number" step="0.1" min="0" max="100" label="กว้าง" suffix="%" :error="$errors->first('settings.badge_photo_width')" />
                        <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_photo_height" type="number" step="0.1" min="0" max="100" label="สูง" suffix="%" :error="$errors->first('settings.badge_photo_height')" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_text_top" type="number" step="0.1" min="0" max="100" label="ข้อความเริ่มที่ (บน)" suffix="%" :error="$errors->first('settings.badge_text_top')" />
                </div>
                <p class="text-xs text-slate-500">กรอบสีแดงคือตำแหน่งรูปผู้เข้าร่วม เส้นสีน้ำเงินคือจุดเริ่มข้อความ ปรับให้ตรงกับพื้นหลังแล้วกด "บันทึกการตั้งค่า" จากนั้นดาวน์โหลด PDF ป้ายชื่อได้ที่เมนู <a href="{{ route('admin.exports') }}" class="text-blue-700 underline">ดาวน์โหลดข้อมูล</a></p>
            </div>
        </x-wirekit::card.body>
    </x-wirekit::card>

    <div class="flex justify-end">
        <x-wirekit::button type="submit">บันทึกการตั้งค่า</x-wirekit::button>
    </div>
</form>
