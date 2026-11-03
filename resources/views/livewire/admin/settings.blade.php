<x-wirekit::form wire:submit="save">
    <x-wirekit::grid template="md:[15rem_1fr]" gap="md" class="items-start">
        <x-wirekit::sidebar label="หมวดการตั้งค่า">
            @foreach ($sections as $item)
                <x-wirekit::sidebar.item as="button" :icon="$item['icon']" :badge="$item['badge']" :active="$section === $item['key']" wire:click="showSection('{{ $item['key'] }}')">{{ $item['label'] }}</x-wirekit::sidebar.item>
            @endforeach
        </x-wirekit::sidebar>

        <x-wirekit::stack gap="md">
            @if ($section === 'event')
                <x-wirekit::card>
                    <x-wirekit::card.header><x-wirekit::heading level="2" size="base">งานและช่วงเวลารับลงทะเบียน</x-wirekit::heading></x-wirekit::card.header>
                    <x-wirekit::card.body>
                        <x-wirekit::grid cols="1 sm:2" gap="md">
                            <x-wirekit::stack gap="none" class="sm:col-span-2"><x-wirekit::input wire:model="settings.event_name" label="ชื่องาน" /></x-wirekit::stack>
                            <x-wirekit::input wire:model="settings.event_dates" label="วันที่จัดงาน" placeholder="เช่น 15–17 มกราคม 2570" />
                            <x-wirekit::input wire:model="settings.event_venue" label="สถานที่" />
                            <x-wirekit::datetime-picker placeholder="เลือกวันและเวลา" wire:model="settings.registration_opens_at" label="เปิดรับลงทะเบียน" hint="เว้นว่าง = เปิดทันที" time-format="24-hour" clearable />
                            <x-wirekit::datetime-picker placeholder="เลือกวันและเวลา" wire:model="settings.registration_closes_at" label="ปิดรับลงทะเบียน" hint="เว้นว่าง = ไม่มีกำหนดปิด" time-format="24-hour" clearable />
                        </x-wirekit::grid>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @elseif ($section === 'fees')
                <x-wirekit::card>
                    <x-wirekit::card.header><x-wirekit::heading level="2" size="base">ค่าลงทะเบียนตามประเภทผู้เข้าร่วม (บาท/คน)</x-wirekit::heading></x-wirekit::card.header>
                    <x-wirekit::card.body>
                        <x-wirekit::stack gap="sm">
                            <x-wirekit::grid cols="1 sm:2" gap="md">
                                @foreach ($types as $type)
                                    <x-wirekit::input wire:model="fees.{{ $type->id }}" type="number" step="0.01" min="0" :label="$type->name_th" suffix="บาท" required />
                                @endforeach
                            </x-wirekit::grid>
                            <x-wirekit::text size="xs" intent="muted">ระบบบันทึกค่าลงทะเบียนของแต่ละคนไว้ ณ ตอนลงทะเบียน การแก้ค่าที่นี่จึงไม่เปลี่ยนยอดของรายชื่อที่มีอยู่แล้ว</x-wirekit::text>
                        </x-wirekit::stack>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @elseif ($section === 'bank')
                <x-wirekit::card>
                    <x-wirekit::card.header><x-wirekit::heading level="2" size="base">บัญชีรับโอน</x-wirekit::heading></x-wirekit::card.header>
                    <x-wirekit::card.body>
                        <x-wirekit::grid cols="1 sm:2" gap="md">
                            <x-wirekit::input wire:model="settings.bank_name" label="ธนาคาร" />
                            <x-wirekit::input wire:model="settings.bank_account_no" label="เลขที่บัญชี" mono />
                            <x-wirekit::stack gap="none" class="sm:col-span-2"><x-wirekit::input wire:model="settings.bank_account_name" label="ชื่อบัญชี" /></x-wirekit::stack>
                        </x-wirekit::grid>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @elseif ($section === 'finance')
                <x-wirekit::card>
                    <x-wirekit::card.header><x-wirekit::heading level="2" size="base">รายงานนำส่งกองคลัง และ PDPA</x-wirekit::heading></x-wirekit::card.header>
                    <x-wirekit::card.body>
                        <x-wirekit::grid cols="1 sm:2" gap="md">
                            <x-wirekit::input wire:model="settings.finance_office_name" label="เรียน (หน่วยงานรับ)" />
                            <x-wirekit::input wire:model="settings.finance_sender_name" label="ชื่อผู้นำส่ง (ในช่องลงนาม)" />
                            <x-wirekit::stack gap="none" class="sm:col-span-2"><x-wirekit::textarea wire:model="settings.pdpa_text" label="ข้อความขอความยินยอม (PDPA)" rows="4" /></x-wirekit::stack>
                        </x-wirekit::grid>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @elseif ($section === 'badge')
                <x-wirekit::card>
                    <x-wirekit::card.header><x-wirekit::heading level="2" size="base">ป้ายชื่อ</x-wirekit::heading></x-wirekit::card.header>
                    <x-wirekit::card.body>
                        <x-wirekit::grid template="lg:[16rem_1fr]" gap="lg">
                            <x-wirekit::stack gap="sm">
                                @if ($badgeBackgroundUrl)
                                    {{-- Positioning canvas: the guides are placed by percentage over the background image. --}}
                                    <div class="relative overflow-hidden rounded-lg border border-(--color-wk-border)">
                                        <x-wirekit::image :src="$badgeBackgroundUrl" alt="พื้นหลังป้ายชื่อ" loading="eager" class="block w-full" />
                                        <div class="absolute border-2 border-dashed border-(--color-wk-danger) bg-(--color-wk-danger)/15"
                                            style="left: {{ (float) $settings['badge_photo_left'] }}%; top: {{ (float) $settings['badge_photo_top'] }}%; width: {{ (float) $settings['badge_photo_width'] }}%; height: {{ (float) $settings['badge_photo_height'] }}%"></div>
                                        <div class="absolute inset-x-[8%] border-t-2 border-dashed border-(--color-wk-info) pt-1 text-center text-[10px] font-semibold text-(--color-wk-info-text)"
                                            style="top: {{ (float) $settings['badge_text_top'] }}%">ชื่อ · ตำแหน่ง · มหาวิทยาลัย</div>
                                    </div>
                                    <x-wirekit::row justify="end">
                                        <x-wirekit::button size="sm" intent="danger" surface="ghost" wire:click="removeBadgeBackground" wire:confirm="ลบพื้นหลังป้ายชื่อ?">ลบพื้นหลัง</x-wirekit::button>
                                    </x-wirekit::row>
                                @else
                                    <x-wirekit::empty-state title="ยังไม่มีพื้นหลัง" class="aspect-2/3 rounded-lg border border-dashed border-(--color-wk-border-strong)" />
                                @endif
                            </x-wirekit::stack>

                            <x-wirekit::stack gap="md">
                                <x-wirekit::stack gap="xs">
                                    <x-wirekit::file-upload wire:model="badgeBackground" accept="image/jpeg,image/png"
                                        :label="$badgeBackgroundUrl ? 'เปลี่ยนพื้นหลังป้ายชื่อ' : 'อัปโหลดพื้นหลังป้ายชื่อ'"
                                        hint="JPG หรือ PNG แนวตั้ง ไม่เกิน 10 MB — ใช้ขนาดหน้ากระดาษตามสัดส่วนของภาพ"
                                        :error="$errors->first('badgeBackground')" />
                                    <x-wirekit::text size="xs" intent="muted" wire:loading wire:target="badgeBackground">กำลังอัปโหลด…</x-wirekit::text>
                                </x-wirekit::stack>

                                <x-wirekit::stack gap="sm">
                                    <x-wirekit::text size="sm" weight="medium">ตำแหน่งกรอบรูป (% ของภาพพื้นหลัง)</x-wirekit::text>
                                    <x-wirekit::grid cols="2 sm:4" gap="sm">
                                        <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_photo_left" type="number" step="0.1" min="0" max="100" label="ซ้าย" suffix="%" :error="$errors->first('settings.badge_photo_left')" />
                                        <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_photo_top" type="number" step="0.1" min="0" max="100" label="บน" suffix="%" :error="$errors->first('settings.badge_photo_top')" />
                                        <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_photo_width" type="number" step="0.1" min="0" max="100" label="กว้าง" suffix="%" :error="$errors->first('settings.badge_photo_width')" />
                                        <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_photo_height" type="number" step="0.1" min="0" max="100" label="สูง" suffix="%" :error="$errors->first('settings.badge_photo_height')" />
                                    </x-wirekit::grid>
                                </x-wirekit::stack>
                                <x-wirekit::grid cols="2 sm:4" gap="sm">
                                    <x-wirekit::input wire:model.live.debounce.400ms="settings.badge_text_top" type="number" step="0.1" min="0" max="100" label="ข้อความเริ่มที่ (บน)" suffix="%" :error="$errors->first('settings.badge_text_top')" />
                                </x-wirekit::grid>
                                <x-wirekit::text size="xs" intent="muted">กรอบสีแดงคือตำแหน่งรูปผู้เข้าร่วม เส้นสีน้ำเงินคือจุดเริ่มข้อความ ปรับให้ตรงกับพื้นหลังแล้วกด "บันทึกการตั้งค่า" จากนั้นดาวน์โหลด PDF ป้ายชื่อได้ที่เมนู <x-wirekit::link :href="route('admin.exports')">ดาวน์โหลดข้อมูล</x-wirekit::link></x-wirekit::text>
                            </x-wirekit::stack>
                        </x-wirekit::grid>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @endif

            <x-wirekit::row justify="end">
                <x-wirekit::button type="submit">บันทึกการตั้งค่า</x-wirekit::button>
            </x-wirekit::row>
        </x-wirekit::stack>
    </x-wirekit::grid>
</x-wirekit::form>
