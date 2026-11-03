<div class="space-y-4">
    <x-wirekit::card>
        <x-wirekit::card.header><h2 class="font-semibold">สร้างรายงานนำส่งใหม่</h2></x-wirekit::card.header>
        <x-wirekit::card.body class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <x-wirekit::input wire:model.live="filters.date_from" type="date" label="ชำระ/อนุมัติตั้งแต่วันที่" />
                <x-wirekit::input wire:model.live="filters.date_to" type="date" label="ถึงวันที่" />
                <x-wirekit::select wire:model.live="filters.university_id" label="มหาวิทยาลัย" placeholder="ทุกมหาวิทยาลัย" :options="$universities" />
                <x-wirekit::select wire:model.live="filters.method" label="ช่องทาง" placeholder="ทุกช่องทาง" :options="['transfer' => 'โอนเงิน', 'onsite' => 'ชำระหน้างาน']" />
            </div>
            <x-wirekit::checkbox wire:model.live="filters.only_new" label="แสดงเฉพาะรายการที่ยังไม่เคยนำส่ง" />

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-blue-50 p-4">
                <div class="text-sm text-blue-900">
                    ชำระแล้วตามเงื่อนไข <b>{{ number_format($previewCount) }}</b> คน · <b>{{ number_format($previewTotal, 2) }}</b> บาท
                    <div class="text-xs text-blue-700">จะนำส่งในรายงานใหม่ {{ number_format($newCount) }} คน (รายการที่เคยนำส่งแล้วจะไม่ถูกนำส่งซ้ำ)</div>
                </div>
                <x-wirekit::button wire:click="create" :disabled="$newCount === 0"
                    wire:confirm="สร้างรายงานนำส่งกองคลัง {{ $newCount }} รายการ? รายการเหล่านี้จะถูกบันทึกว่านำส่งแล้ว">สร้างรายงานนำส่ง</x-wirekit::button>
            </div>
        </x-wirekit::card.body>
    </x-wirekit::card>

    <x-wirekit::card>
        <x-wirekit::card.header><h2 class="font-semibold">รายงานที่นำส่งแล้ว</h2></x-wirekit::card.header>
        <x-wirekit::table hoverable stack-below="md">
            <x-wirekit::table.head>
                <x-wirekit::table.row>
                    <x-wirekit::table.th>เลขที่นำส่ง</x-wirekit::table.th>
                    <x-wirekit::table.th>วันที่สร้าง</x-wirekit::table.th>
                    <x-wirekit::table.th>ช่วงวันที่</x-wirekit::table.th>
                    <x-wirekit::table.th align="right">รายการ</x-wirekit::table.th>
                    <x-wirekit::table.th align="right">ยอดรวม</x-wirekit::table.th>
                    <x-wirekit::table.th>ผู้สร้าง</x-wirekit::table.th>
                    <x-wirekit::table.th>ดาวน์โหลด</x-wirekit::table.th>
                </x-wirekit::table.row>
            </x-wirekit::table.head>
            <x-wirekit::table.body>
                @forelse ($submissions as $s)
                    <x-wirekit::table.row wire:key="s-{{ $s->id }}">
                        <x-wirekit::table.td><span class="font-mono">{{ $s->submission_no }}</span></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $s->created_at->format('d/m/Y H:i') }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $s->date_from?->format('d/m/Y') ?? 'ทั้งหมด' }} – {{ $s->date_to?->format('d/m/Y') ?? 'ปัจจุบัน' }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ $s->item_count }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ number_format($s->total_amount, 2) }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $s->creator->name }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            <div class="flex gap-1">
                                <x-wirekit::button size="sm" surface="soft" :href="route('admin.finance.download', [$s, 'pdf'])">PDF</x-wirekit::button>
                                <x-wirekit::button size="sm" intent="success" surface="soft" :href="route('admin.finance.download', [$s, 'xlsx'])">Excel</x-wirekit::button>
                                <x-wirekit::button size="sm" intent="neutral" surface="soft" :href="route('admin.finance.download', [$s, 'zip'])">ZIP สลิป</x-wirekit::button>
                            </div>
                        </x-wirekit::table.td>
                    </x-wirekit::table.row>
                @empty
                    <x-wirekit::table.row><x-wirekit::table.td colspan="7"><span class="text-slate-500">ยังไม่มีรายงาน</span></x-wirekit::table.td></x-wirekit::table.row>
                @endforelse
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>
</div>
