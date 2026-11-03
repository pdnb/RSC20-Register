<x-wirekit::stack gap="md">
    <x-wirekit::card>
        <x-wirekit::card.body>
            <x-wirekit::stack gap="sm">
                <x-wirekit::text size="sm" intent="muted">อีเมลที่อยู่ในรายการนี้เท่านั้นที่เข้าสู่ระบบด้วย Auth0 ได้ (อีเมลต้องยืนยันแล้วใน Auth0)</x-wirekit::text>
                <x-wirekit::form wire:submit="add">
                    <x-wirekit::grid cols="1 sm:4" gap="sm" align="end">
                        <x-wirekit::input wire:model="email" type="email" label="อีเมล" required />
                        <x-wirekit::input wire:model="name" label="ชื่อ" required />
                        <x-wirekit::select wire:model="role" label="สิทธิ์" :options="['staff' => 'เจ้าหน้าที่เช็กอิน', 'admin' => 'ผู้ดูแลระบบ']" />
                        <x-wirekit::button type="submit">เพิ่มสิทธิ์</x-wirekit::button>
                    </x-wirekit::grid>
                </x-wirekit::form>
            </x-wirekit::stack>
        </x-wirekit::card.body>
    </x-wirekit::card>

    <x-wirekit::card>
        <x-wirekit::table hoverable compact stack-below="md">
            <x-wirekit::table.head>
                <x-wirekit::table.row>
                    <x-wirekit::table.th>อีเมล</x-wirekit::table.th>
                    <x-wirekit::table.th>ชื่อ</x-wirekit::table.th>
                    <x-wirekit::table.th>สิทธิ์</x-wirekit::table.th>
                    <x-wirekit::table.th>ผูก Auth0</x-wirekit::table.th>
                    <x-wirekit::table.th>เข้าสู่ระบบล่าสุด</x-wirekit::table.th>
                    <x-wirekit::table.th></x-wirekit::table.th>
                </x-wirekit::table.row>
            </x-wirekit::table.head>
            <x-wirekit::table.body>
                @foreach ($users as $u)
                    <x-wirekit::table.row wire:key="u-{{ $u->id }}">
                        <x-wirekit::table.td>{{ $u->email }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $u->name }}</x-wirekit::table.td>
                        <x-wirekit::table.td><x-wirekit::badge size="sm" :intent="$u->isAdmin() ? 'primary' : 'neutral'">{{ $u->role->label() }}</x-wirekit::badge></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $u->auth0_sub ? 'แล้ว' : 'ยังไม่เคยเข้า' }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $u->last_login_at?->format('d/m/Y H:i') ?? '-' }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">
                            @if ($u->id !== auth()->id())
                                <x-wirekit::button size="sm" :intent="$u->is_active ? 'danger' : 'success'" surface="ghost" wire:click="toggleActive({{ $u->id }})">{{ $u->is_active ? 'ระงับ' : 'เปิดใช้' }}</x-wirekit::button>
                            @endif
                        </x-wirekit::table.td>
                    </x-wirekit::table.row>
                @endforeach
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>
</x-wirekit::stack>
