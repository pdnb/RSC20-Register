<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-slate-500">ผู้ประสานงานเข้าสู่ระบบด้วย username + รหัสผ่านที่ออกจากหน้านี้ และต้องเปลี่ยนรหัสผ่านเมื่อเข้าครั้งแรก</p>
        <div class="flex gap-2">
            @if ($missing > 0)
                <x-wirekit::button intent="neutral" surface="outline" wire:click="createMissing"
                    wire:confirm="สร้างบัญชีให้มหาวิทยาลัยที่ยังไม่มีบัญชี {{ $missing }} แห่ง?">สร้างครบทุกมหาวิทยาลัย ({{ $missing }})</x-wirekit::button>
            @endif
            <x-wirekit::button wire:click="create">+ สร้างบัญชี</x-wirekit::button>
        </div>
    </div>

    @if ($issued)
        <x-wirekit::callout intent="warning" title="รหัสผ่านที่ออกใหม่ — แสดงครั้งเดียว">
            <p class="mb-3">คัดลอกหรือดาวน์โหลด PDF เก็บไว้ก่อนออกจากหน้านี้ ระบบเก็บรหัสผ่านแบบเข้ารหัสทางเดียว จึงเรียกดูภายหลังไม่ได้</p>
            <div class="mb-3 overflow-x-auto rounded-lg bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs text-slate-500"><tr><th class="px-3 py-2">มหาวิทยาลัย</th><th class="px-3 py-2">username</th><th class="px-3 py-2">รหัสผ่าน</th></tr></thead>
                    <tbody>
                        @foreach ($issued as $row)
                            <tr class="border-t border-slate-100"><td class="px-3 py-1.5">{{ $row['university'] }}</td><td class="px-3 py-1.5 font-mono">{{ $row['username'] }}</td><td class="px-3 py-1.5 font-mono">{{ $row['password'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-wirekit::button size="sm" wire:click="downloadIssued">ดาวน์โหลด PDF ใบแจ้งบัญชี</x-wirekit::button>
        </x-wirekit::callout>
    @endif

    <x-wirekit::card>
        <x-wirekit::table hoverable compact stack-below="md">
            <x-wirekit::table.head>
                <x-wirekit::table.row>
                    <x-wirekit::table.th>มหาวิทยาลัย</x-wirekit::table.th>
                    <x-wirekit::table.th>username</x-wirekit::table.th>
                    <x-wirekit::table.th>ชื่อ / โทร</x-wirekit::table.th>
                    <x-wirekit::table.th>เข้าสู่ระบบล่าสุด</x-wirekit::table.th>
                    <x-wirekit::table.th>สถานะ</x-wirekit::table.th>
                    <x-wirekit::table.th></x-wirekit::table.th>
                </x-wirekit::table.row>
            </x-wirekit::table.head>
            <x-wirekit::table.body>
                @forelse ($accounts as $a)
                    <x-wirekit::table.row wire:key="c-{{ $a->id }}">
                        <x-wirekit::table.td>{{ $a->university?->name }}</x-wirekit::table.td>
                        <x-wirekit::table.td><span class="font-mono">{{ $a->username }}</span></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $a->name }}<div class="text-xs text-slate-500">{{ $a->phone }}</div></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $a->last_login_at?->format('d/m/Y H:i') ?? 'ยังไม่เคย' }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            <x-wirekit::badge size="sm" :intent="$a->is_active ? 'success' : 'danger'">{{ $a->is_active ? 'ใช้งาน' : 'ระงับ' }}</x-wirekit::badge>
                            @if ($a->must_change_password)<x-wirekit::badge size="sm" intent="warning">รอเปลี่ยนรหัส</x-wirekit::badge>@endif
                        </x-wirekit::table.td>
                        <x-wirekit::table.td align="right">
                            <div class="flex justify-end gap-1">
                                <x-wirekit::button size="sm" intent="neutral" surface="ghost" wire:click="resetPassword({{ $a->id }})" wire:confirm="รีเซ็ตรหัสผ่านของ {{ $a->username }}?">รีเซ็ตรหัส</x-wirekit::button>
                                <x-wirekit::button size="sm" :intent="$a->is_active ? 'danger' : 'success'" surface="ghost" wire:click="toggleActive({{ $a->id }})">{{ $a->is_active ? 'ระงับ' : 'เปิดใช้' }}</x-wirekit::button>
                            </div>
                        </x-wirekit::table.td>
                    </x-wirekit::table.row>
                @empty
                    <x-wirekit::table.row><x-wirekit::table.td colspan="6"><span class="text-slate-500">ยังไม่มีบัญชี</span></x-wirekit::table.td></x-wirekit::table.row>
                @endforelse
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>

    <x-wirekit::modal name="coordinator-form">
        <x-wirekit::modal.header>สร้างบัญชีผู้ประสานงาน</x-wirekit::modal.header>
        <x-wirekit::modal.body>
            <form wire:submit="save" id="coordinator-form" class="space-y-4">
                <x-wirekit::select wire:model.live="form.university_id" label="มหาวิทยาลัย" placeholder="เลือกมหาวิทยาลัย" :options="$universities" required />
                <x-wirekit::input wire:model="form.username" label="username" mono required />
                <x-wirekit::input wire:model="form.name" label="ชื่อผู้ประสานงาน" required />
                <x-wirekit::input wire:model="form.phone" label="เบอร์ติดต่อ" />
                <p class="text-xs text-slate-500">ระบบจะสุ่มรหัสผ่านให้และแสดงเพียงครั้งเดียว</p>
            </form>
        </x-wirekit::modal.body>
        <x-wirekit::modal.footer>
            <x-wirekit::modal.close><x-wirekit::button intent="neutral" surface="ghost">ยกเลิก</x-wirekit::button></x-wirekit::modal.close>
            <x-wirekit::button type="submit" form="coordinator-form">สร้างบัญชี</x-wirekit::button>
        </x-wirekit::modal.footer>
    </x-wirekit::modal>
</div>
