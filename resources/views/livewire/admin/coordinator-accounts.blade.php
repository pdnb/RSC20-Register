<x-wirekit::stack gap="md">
    <x-wirekit::row gap="sm" align="center" justify="between" wrap>
        <x-wirekit::text size="sm" intent="muted">ผู้ประสานงานใช้ username และรหัสผ่านที่สร้างจากหน้านี้เข้าสู่ระบบ และต้องเปลี่ยนรหัสผ่านเมื่อเข้าใช้งานครั้งแรก</x-wirekit::text>
        <x-wirekit::row gap="sm">
            @if ($missing > 0)
                <x-wirekit::button intent="neutral" surface="outline" wire:click="createMissing"
                    wire:confirm="สร้างบัญชีให้มหาวิทยาลัยที่ยังไม่มีบัญชี {{ $missing }} แห่ง?">สร้างครบทุกมหาวิทยาลัย ({{ $missing }})</x-wirekit::button>
            @endif
            <x-wirekit::button wire:click="create">+ สร้างบัญชี</x-wirekit::button>
        </x-wirekit::row>
    </x-wirekit::row>

    @if ($issued)
        <x-wirekit::callout intent="warning" title="รหัสผ่านใหม่ แสดงเพียงครั้งเดียว">
            <x-wirekit::stack gap="sm">
                <x-wirekit::text size="inherit" intent="inherit">กรุณาคัดลอกหรือดาวน์โหลดไฟล์ PDF หรือ Excel เก็บไว้ก่อนออกจากหน้านี้ เพราะจะเปิดดูรหัสผ่านอีกไม่ได้</x-wirekit::text>
                <x-wirekit::card>
                    <x-wirekit::table compact>
                        <x-wirekit::table.head>
                            <x-wirekit::table.row>
                                <x-wirekit::table.th>มหาวิทยาลัย</x-wirekit::table.th>
                                <x-wirekit::table.th>username</x-wirekit::table.th>
                                <x-wirekit::table.th>รหัสผ่าน</x-wirekit::table.th>
                            </x-wirekit::table.row>
                        </x-wirekit::table.head>
                        <x-wirekit::table.body>
                            @foreach ($issued as $row)
                                <x-wirekit::table.row>
                                    <x-wirekit::table.td>{{ $row['university'] }}</x-wirekit::table.td>
                                    <x-wirekit::table.td><x-wirekit::text as="span" size="sm" family="mono">{{ $row['username'] }}</x-wirekit::text></x-wirekit::table.td>
                                    <x-wirekit::table.td><x-wirekit::text as="span" size="sm" family="mono">{{ $row['password'] }}</x-wirekit::text></x-wirekit::table.td>
                                </x-wirekit::table.row>
                            @endforeach
                        </x-wirekit::table.body>
                    </x-wirekit::table>
                </x-wirekit::card>
                <x-wirekit::row gap="sm" wrap>
                    <x-wirekit::button size="sm" wire:click="downloadIssued">ดาวน์โหลด PDF ใบแจ้งบัญชี</x-wirekit::button>
                    <x-wirekit::button size="sm" intent="neutral" surface="outline" wire:click="downloadIssuedExcel">ดาวน์โหลด Excel</x-wirekit::button>
                </x-wirekit::row>
            </x-wirekit::stack>
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
                        <x-wirekit::table.td><x-wirekit::text as="span" size="sm" family="mono">{{ $a->username }}</x-wirekit::text></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $a->name }}<x-wirekit::text size="xs" intent="muted" leading="tight">{{ $a->phone }}</x-wirekit::text></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $a->last_login_at?->format('d/m/Y H:i') ?? 'ยังไม่เคย' }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            <x-wirekit::badge size="sm" :intent="$a->is_active ? 'success' : 'danger'">{{ $a->is_active ? 'ใช้งาน' : 'ระงับ' }}</x-wirekit::badge>
                            @if ($a->must_change_password)<x-wirekit::badge size="sm" intent="warning">รอเปลี่ยนรหัส</x-wirekit::badge>@endif
                        </x-wirekit::table.td>
                        <x-wirekit::table.td align="right">
                            <x-wirekit::row gap="xs" justify="end">
                                <x-wirekit::button size="sm" intent="neutral" surface="ghost" wire:click="resetPassword({{ $a->id }})" wire:confirm="รีเซ็ตรหัสผ่านของ {{ $a->username }}?">รีเซ็ตรหัส</x-wirekit::button>
                                <x-wirekit::button size="sm" :intent="$a->is_active ? 'danger' : 'success'" surface="ghost" wire:click="toggleActive({{ $a->id }})">{{ $a->is_active ? 'ระงับ' : 'เปิดใช้' }}</x-wirekit::button>
                            </x-wirekit::row>
                        </x-wirekit::table.td>
                    </x-wirekit::table.row>
                @empty
                    <x-wirekit::table.row><x-wirekit::table.td colspan="6"><x-wirekit::text as="span" size="sm" intent="muted">ยังไม่มีบัญชี</x-wirekit::text></x-wirekit::table.td></x-wirekit::table.row>
                @endforelse
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>

    <x-wirekit::modal name="coordinator-form" dismissible="false">
        <x-wirekit::modal.header>สร้างบัญชีผู้ประสานงาน</x-wirekit::modal.header>
        <x-wirekit::modal.body>
            <x-wirekit::form wire:submit="save" id="coordinator-form">
                <x-wirekit::stack gap="md">
                    <x-wirekit::select wire:model.live="form.university_id" label="มหาวิทยาลัย" placeholder="เลือกมหาวิทยาลัย" :options="$universities" required />
                    <x-wirekit::input wire:model="form.username" label="username" mono required />
                    <x-wirekit::input wire:model="form.name" label="ชื่อผู้ประสานงาน" required />
                    <x-wirekit::input wire:model="form.phone" label="เบอร์ติดต่อ" />
                    <x-wirekit::text size="xs" intent="muted">ระบบจะสุ่มรหัสผ่านให้และแสดงเพียงครั้งเดียว</x-wirekit::text>
                </x-wirekit::stack>
            </x-wirekit::form>
        </x-wirekit::modal.body>
        <x-wirekit::modal.footer>
            <x-wirekit::modal.close><x-wirekit::button intent="neutral" surface="ghost">ยกเลิก</x-wirekit::button></x-wirekit::modal.close>
            <x-wirekit::button type="submit" form="coordinator-form">สร้างบัญชี</x-wirekit::button>
        </x-wirekit::modal.footer>
    </x-wirekit::modal>
</x-wirekit::stack>
