<x-wirekit::stack gap="md">
    <x-wirekit::grid cols="1 sm:3" gap="sm">
        <x-wirekit::input wire:model.live.debounce.300ms="search" label="ค้นหา" placeholder="ชื่อ / เบอร์โทร / รหัส" clearable />
        <x-wirekit::multi-select wire:model.live="university" label="มหาวิทยาลัย" placeholder="ทุกมหาวิทยาลัย" :options="$universities" />
        <x-wirekit::multi-select wire:model.live="type" label="ประเภท" placeholder="ทุกประเภท" :options="$types" />
    </x-wirekit::grid>

    <x-wirekit::card>
        <x-wirekit::table hoverable compact stack-below="lg">
            <x-wirekit::table.head>
                <x-wirekit::table.row>
                    <x-wirekit::table.th>รหัส</x-wirekit::table.th>
                    <x-wirekit::table.th>ชื่อ-นามสกุล</x-wirekit::table.th>
                    <x-wirekit::table.th>มหาวิทยาลัย</x-wirekit::table.th>
                    <x-wirekit::table.th>ประเภท / ตำแหน่ง</x-wirekit::table.th>
                    <x-wirekit::table.th>โทรศัพท์</x-wirekit::table.th>
                    <x-wirekit::table.th>เลขบัตร</x-wirekit::table.th>
                    <x-wirekit::table.th>อาหาร / แพ้</x-wirekit::table.th>
                    <x-wirekit::table.th>เสื้อ</x-wirekit::table.th>
                    <x-wirekit::table.th>เช็กอิน</x-wirekit::table.th>
                </x-wirekit::table.row>
            </x-wirekit::table.head>
            <x-wirekit::table.body>
                @forelse ($participants as $p)
                    <x-wirekit::table.row wire:key="pa-{{ $p->id }}">
                        <x-wirekit::table.td><x-wirekit::text as="span" size="xs" family="mono">{{ $p->code }}</x-wirekit::text></x-wirekit::table.td>
                        <x-wirekit::table.td><x-wirekit::link :href="route('admin.groups.show', $p->group)" underline="hover">{{ $p->displayName() }}</x-wirekit::link></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->group->university->name }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->type->name_th }}<x-wirekit::text size="xs" intent="muted" leading="tight">{{ $p->position }}</x-wirekit::text></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->mobile_phone }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            @if (isset($revealed[$p->id]))
                                <x-wirekit::text as="span" size="xs" family="mono">{{ $revealed[$p->id] }}</x-wirekit::text>
                            @else
                                <x-wirekit::button size="xs" intent="neutral" surface="ghost" class="font-mono" wire:click="reveal({{ $p->id }})" title="แสดงเลขเต็ม (บันทึก log)">{{ $p->maskedNationalId() }}</x-wirekit::button>
                            @endif
                        </x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->food_type }}@if ($p->food_allergy)<x-wirekit::text size="xs" intent="danger" leading="tight">แพ้: {{ $p->food_allergy }}</x-wirekit::text>@endif</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->shirtSizeLabel() }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            @if ($p->checked_in_at)
                                <x-wirekit::badge size="sm" intent="success">{{ $p->checked_in_at->format('d/m H:i') }}</x-wirekit::badge>
                            @else
                                <x-wirekit::text as="span" size="xs" intent="subtle">-</x-wirekit::text>
                            @endif
                        </x-wirekit::table.td>
                    </x-wirekit::table.row>
                @empty
                    <x-wirekit::table.row><x-wirekit::table.td colspan="9"><x-wirekit::text as="span" size="sm" intent="muted">ไม่พบข้อมูล</x-wirekit::text></x-wirekit::table.td></x-wirekit::table.row>
                @endforelse
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>

    <x-wirekit::pagination :paginator="$participants" livewire />
</x-wirekit::stack>
