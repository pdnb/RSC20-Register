<div class="space-y-4">
    <div class="grid gap-3 sm:grid-cols-3">
        <x-wirekit::input wire:model.live.debounce.300ms="search" label="ค้นหา" placeholder="ชื่อ / เบอร์โทร / รหัส" clearable />
        <x-wirekit::select wire:model.live="university" label="มหาวิทยาลัย" placeholder="ทุกมหาวิทยาลัย" :options="$universities" />
        <x-wirekit::select wire:model.live="type" label="ประเภท" placeholder="ทุกประเภท" :options="$types" />
    </div>

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
                        <x-wirekit::table.td><span class="font-mono text-xs">{{ $p->code }}</span></x-wirekit::table.td>
                        <x-wirekit::table.td><a class="text-blue-700 hover:underline" href="{{ route('admin.groups.show', $p->group) }}">{{ $p->displayName() }}</a></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->group->university->name }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->type->name_th }}<div class="text-xs text-slate-500">{{ $p->position }}</div></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->mobile_phone }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            @if (isset($revealed[$p->id]))
                                <span class="font-mono text-xs">{{ $revealed[$p->id] }}</span>
                            @else
                                <button type="button" class="font-mono text-xs text-slate-500 hover:text-blue-700" wire:click="reveal({{ $p->id }})" title="แสดงเลขเต็ม (บันทึก log)">{{ $p->maskedNationalId() }}</button>
                            @endif
                        </x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->food_type }}@if ($p->food_allergy)<div class="text-xs text-red-600">แพ้: {{ $p->food_allergy }}</div>@endif</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $p->shirt_size }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            @if ($p->checked_in_at)
                                <x-wirekit::badge size="sm" intent="success">{{ $p->checked_in_at->format('d/m H:i') }}</x-wirekit::badge>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </x-wirekit::table.td>
                    </x-wirekit::table.row>
                @empty
                    <x-wirekit::table.row><x-wirekit::table.td colspan="9"><span class="text-slate-500">ไม่พบข้อมูล</span></x-wirekit::table.td></x-wirekit::table.row>
                @endforelse
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>

    {{ $participants->links() }}
</div>
