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
            @elseif ($section === 'code')
                <x-wirekit::card>
                    <x-wirekit::card.header><x-wirekit::heading level="2" size="base">รูปแบบรหัสผู้เข้าร่วม</x-wirekit::heading></x-wirekit::card.header>
                    <x-wirekit::card.body>
                        <x-wirekit::stack gap="md">
                            <x-wirekit::grid cols="1 sm:3" gap="md">
                                <x-wirekit::input wire:model.live.debounce.400ms="settings.participant_code_prefix" label="คำนำหน้ารหัส" placeholder="เช่น RSC20-" hint="A–Z, 0–9, - และ _ ไม่เกิน 12 ตัว" mono required :error="$errors->first('settings.participant_code_prefix')" />
                                <x-wirekit::input wire:model.live.debounce.400ms="settings.participant_code_digits" type="number" min="1" max="8" label="จำนวนหลักของเลขลำดับ" hint="4 = 0001" required :error="$errors->first('settings.participant_code_digits')" />
                                <x-wirekit::input wire:model.live.debounce.400ms="settings.participant_code_start" type="number" min="1" label="เลขเริ่มต้น" required :error="$errors->first('settings.participant_code_start')" />
                            </x-wirekit::grid>
                            <x-wirekit::text size="sm">รหัสถัดไป: <x-wirekit::text as="span" weight="semibold" family="mono">{{ $codePreview ?? '—' }}</x-wirekit::text></x-wirekit::text>
                            <x-wirekit::text size="xs" intent="muted">การเปลี่ยนรูปแบบมีผลกับผู้เข้าร่วมที่เพิ่มหลังจากนี้ รหัสของผู้เข้าร่วมเดิมไม่เปลี่ยน ระบบจะต่อเลขจากรหัสสูงสุดที่ใช้คำนำหน้าเดียวกัน และไม่ต่ำกว่าเลขเริ่มต้น</x-wirekit::text>
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
                        <x-wirekit::stack gap="lg">
                            <x-wirekit::grid cols="1 sm:2" gap="lg">
                                @foreach ($badgeBackgrounds as $background)
                                    <x-wirekit::stack gap="sm">
                                        <x-wirekit::text size="sm" weight="medium">{{ $background['label'] }}</x-wirekit::text>
                                        {{-- Positioning canvas: the guides are placed by percentage over the background image. --}}
                                        <div class="relative overflow-hidden rounded-lg border border-(--color-wk-border)">
                                            <x-wirekit::image :src="route('admin.badge-background', $background['kind'])" :alt="$background['label']" loading="eager" class="block w-full" />
                                            <div class="absolute border-2 border-dashed border-(--color-wk-danger) bg-(--color-wk-danger)/15"
                                                style="left: {{ (float) $settings['badge_photo_left'] }}%; top: {{ (float) $settings['badge_photo_top'] }}%; width: {{ (float) $settings['badge_photo_width'] }}%; height: {{ (float) $settings['badge_photo_height'] }}%"></div>
                                            <div class="absolute inset-x-[8%] border-t-2 border-dashed border-(--color-wk-info) pt-1 text-center text-[10px] font-semibold text-(--color-wk-info-text)"
                                                style="top: {{ (float) $settings['badge_text_top'] }}%">ชื่อ · ตำแหน่ง · มหาวิทยาลัย · บทบาท</div>
                                        </div>
                                    </x-wirekit::stack>
                                @endforeach
                            </x-wirekit::grid>

                            <x-wirekit::stack gap="md">
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
                                <x-wirekit::text size="xs" intent="muted">กรอบสีแดงคือตำแหน่งรูป เส้นสีน้ำเงินคือจุดเริ่มชื่อ ระบบเลือกแบบตามรูปแบบการลงทะเบียน ผู้ที่ลงทั้งประชุมและบอร์ดเกมได้สองใบ ปรับตำแหน่งแล้วกด "บันทึกการตั้งค่า" จากนั้นดาวน์โหลดได้ที่เมนู <x-wirekit::link :href="route('admin.exports')">ดาวน์โหลดข้อมูล</x-wirekit::link></x-wirekit::text>
                            </x-wirekit::stack>
                        </x-wirekit::stack>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @elseif ($section === 'line')
                <x-wirekit::card>
                    <x-wirekit::card.header><x-wirekit::heading level="2" size="base">กลุ่ม LINE ผู้ประสานงาน</x-wirekit::heading></x-wirekit::card.header>
                    <x-wirekit::card.body>
                        <x-wirekit::stack gap="md">
                            <x-wirekit::input wire:model="settings.line_group_url" type="url" label="ลิงก์เชิญเข้ากลุ่ม" placeholder="https://line.me/ti/g/..." :error="$errors->first('settings.line_group_url')" />
                            <x-wirekit::file-upload wire:model="lineQr" accept="image/jpeg,image/png"
                                :label="$lineQrUrl ? 'เปลี่ยนรูป QR' : 'อัปโหลดรูป QR (ไม่บังคับ)'"
                                hint="JPG หรือ PNG ไม่เกิน 5 MB — ถ้าไม่อัปโหลด ระบบจะสร้าง QR จากลิงก์เชิญ"
                                :error="$errors->first('lineQr')" />
                            <x-wirekit::text size="xs" intent="muted" wire:loading wire:target="lineQr">กำลังอัปโหลด…</x-wirekit::text>
                            @if ($lineQrUrl)
                                <x-wirekit::stack gap="sm" align="start">
                                    <x-wirekit::image :src="$lineQrUrl" alt="QR Code กลุ่ม LINE" loading="eager" class="w-48 rounded-lg border border-(--color-wk-border)" />
                                    <x-wirekit::button size="sm" intent="danger" surface="ghost" wire:click="removeLineQr" wire:confirm="ลบรูป QR กลุ่ม LINE?">ลบรูป QR</x-wirekit::button>
                                </x-wirekit::stack>
                            @endif
                            <x-wirekit::text size="xs" intent="muted">เมื่อมีลิงก์หรือรูป QR ผู้ประสานงานจะเห็นหน้าต่างชวนเข้ากลุ่มหนึ่งครั้งหลังเข้าสู่ระบบ และเปิดดูซ้ำได้จากปุ่ม "กลุ่ม LINE" ลิงก์มีผลหลังกด "บันทึกการตั้งค่า"</x-wirekit::text>
                        </x-wirekit::stack>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @endif

            <x-wirekit::row justify="end">
                <x-wirekit::button type="submit">บันทึกการตั้งค่า</x-wirekit::button>
            </x-wirekit::row>
        </x-wirekit::stack>
    </x-wirekit::grid>
</x-wirekit::form>
