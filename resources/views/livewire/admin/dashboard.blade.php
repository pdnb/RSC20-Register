<div class="space-y-6">
    @if ($feesMissing)
        <x-wirekit::callout intent="warning">ยังไม่ได้กำหนดค่าลงทะเบียนบางประเภท — <a href="{{ route('admin.settings') }}" class="underline">ไปหน้าตั้งค่า</a></x-wirekit::callout>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['ผู้ลงทะเบียน', number_format($stats['participants']).' คน', $stats['universities'].' มหาวิทยาลัย'],
            ['ยอดที่ต้องได้รับ', number_format($stats['expected'], 2).' ฿', 'ชำระแล้ว '.number_format($stats['paid'], 2).' ฿'],
            ['สลิปรอตรวจ', number_format($stats['pendingSlips']).' กลุ่ม', ''],
            ['เช็กอินแล้ว', number_format($stats['checkedIn']).' คน', $stats['participants'] ? round($stats['checkedIn'] * 100 / $stats['participants']).'%' : ''],
        ] as [$label, $value, $sub])
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $value }}</div>
                <div class="text-xs text-slate-500">{{ $sub }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-wirekit::card>
            <x-wirekit::card.header><h2 class="font-semibold">ตามประเภท</h2></x-wirekit::card.header>
            <x-wirekit::card.body class="space-y-2 text-sm">
                @foreach ($byType as $t)
                    <div class="flex justify-between"><span>{{ $t->name_th }} <span class="text-slate-400">({{ number_format($t->fee, 0) }} ฿)</span></span><b>{{ $t->participants_count }}</b></div>
                @endforeach
            </x-wirekit::card.body>
        </x-wirekit::card>
        <x-wirekit::card>
            <x-wirekit::card.header><h2 class="font-semibold">ไซซ์เสื้อ</h2></x-wirekit::card.header>
            <x-wirekit::card.body class="grid grid-cols-3 gap-2 text-sm">
                @foreach (\App\Models\Participant::SHIRT_SIZES as $size)
                    <div class="rounded-lg bg-slate-50 px-3 py-2 text-center"><div class="text-xs text-slate-500">{{ $size }}</div><div class="font-semibold">{{ $byShirt[$size] ?? 0 }}</div></div>
                @endforeach
            </x-wirekit::card.body>
        </x-wirekit::card>
        <x-wirekit::card>
            <x-wirekit::card.header><h2 class="font-semibold">อาหาร</h2></x-wirekit::card.header>
            <x-wirekit::card.body class="space-y-2 text-sm">
                @forelse ($byFood as $food => $count)
                    <div class="flex justify-between"><span>{{ $food }}</span><b>{{ $count }}</b></div>
                @empty
                    <p class="text-slate-500">-</p>
                @endforelse
                <div class="flex justify-between border-t border-slate-100 pt-2 text-red-600"><span>มีอาหารที่แพ้</span><b>{{ $allergies }}</b></div>
            </x-wirekit::card.body>
        </x-wirekit::card>
    </div>

    <x-wirekit::card>
        <x-wirekit::card.header><h2 class="font-semibold">รายมหาวิทยาลัย</h2></x-wirekit::card.header>
        <x-wirekit::table hoverable compact stack-below="md">
            <x-wirekit::table.head>
                <x-wirekit::table.row>
                    <x-wirekit::table.th>มหาวิทยาลัย</x-wirekit::table.th>
                    <x-wirekit::table.th align="right">ผู้เข้าร่วม</x-wirekit::table.th>
                    <x-wirekit::table.th align="right">ยอดเงิน</x-wirekit::table.th>
                    <x-wirekit::table.th>สถานะ</x-wirekit::table.th>
                    <x-wirekit::table.th align="right">เช็กอิน</x-wirekit::table.th>
                </x-wirekit::table.row>
            </x-wirekit::table.head>
            <x-wirekit::table.body>
                @foreach ($byUniversity as $u)
                    <x-wirekit::table.row>
                        <x-wirekit::table.td>
                            @if ($u->group)
                                <a href="{{ route('admin.groups.show', $u->group) }}" class="text-blue-700 hover:underline">{{ $u->name }}</a>
                            @else
                                <span class="text-slate-400">{{ $u->name }}</span>
                            @endif
                        </x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ $u->group?->participants_count ?? 0 }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ number_format($u->group?->total_amount ?? 0, 2) }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            @if ($u->group)
                                <x-wirekit::badge size="sm" :intent="$u->group->payment_status->intent()">{{ $u->group->payment_status->label() }}</x-wirekit::badge>
                            @else
                                <span class="text-xs text-slate-400">ยังไม่ลงทะเบียน</span>
                            @endif
                        </x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ $u->group?->checked_in_count ?? 0 }}</x-wirekit::table.td>
                    </x-wirekit::table.row>
                @endforeach
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>
</div>
