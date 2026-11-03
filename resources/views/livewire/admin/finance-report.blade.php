<x-wirekit::stack gap="md">
    <x-wirekit::card>
        <x-wirekit::card.header><x-wirekit::heading level="2" size="base">สร้างรายงานนำส่งใหม่</x-wirekit::heading></x-wirekit::card.header>
        <x-wirekit::card.body>
            <x-wirekit::stack gap="md">
                <x-wirekit::grid cols="1 sm:2 lg:5" gap="sm">
                    <x-wirekit::date-picker placeholder="เลือกวันที่" wire:model.live="filters.date_from" label="ชำระ/อนุมัติตั้งแต่วันที่" popover clearable />
                    <x-wirekit::date-picker placeholder="เลือกวันที่" wire:model.live="filters.date_to" label="ถึงวันที่" :min="$filters['date_from'] ?: null" popover clearable />
                    <x-wirekit::multi-select wire:model.live="filters.university_id" label="มหาวิทยาลัย" placeholder="ทุกมหาวิทยาลัย" :options="$universities" />
                    <x-wirekit::select wire:model.live="filters.method" label="ช่องทาง" placeholder="ทุกช่องทาง" :options="['transfer' => 'โอนเงิน', 'onsite' => 'ชำระหน้างาน']" />
                    <x-wirekit::multi-select wire:model.live="filters.status" label="สถานะ" placeholder="ทุกสถานะ" :options="$statuses" />
                </x-wirekit::grid>
                <x-wirekit::checkbox wire:model.live="filters.only_new" label="แสดงเฉพาะรายการที่ยังไม่เคยนำส่ง" />

                <x-wirekit::callout intent="info" :icon="false">
                    <x-wirekit::row gap="md" align="center" justify="between" wrap>
                        <x-wirekit::stack gap="none">
                            <x-wirekit::text size="sm" intent="inherit">
                                พบ <x-wirekit::text as="strong" size="inherit" intent="inherit" weight="semibold">{{ number_format($previewCount) }}</x-wirekit::text> คน
                                รวม <x-wirekit::text as="strong" size="inherit" intent="inherit" weight="semibold">{{ number_format($previewTotal, 2) }}</x-wirekit::text> บาท
                            </x-wirekit::text>
                            <x-wirekit::text size="xs" intent="inherit">นำส่งได้ {{ number_format($newCount) }} คน ไม่รวมรายการที่เคยนำส่งไปแล้ว</x-wirekit::text>
                        </x-wirekit::stack>
                        <x-wirekit::button wire:click="create" :disabled="$newCount === 0"
                            wire:confirm="สร้างรายงานนำส่งกองคลัง {{ $newCount }} รายการ? เมื่อสร้างแล้วจะนับว่านำส่งเรียบร้อย">สร้างรายงานนำส่ง</x-wirekit::button>
                    </x-wirekit::row>
                </x-wirekit::callout>
            </x-wirekit::stack>
        </x-wirekit::card.body>
    </x-wirekit::card>

    <x-wirekit::card>
        <x-wirekit::card.header><x-wirekit::heading level="2" size="base">รายงานที่นำส่งแล้ว</x-wirekit::heading></x-wirekit::card.header>
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
                        <x-wirekit::table.td><x-wirekit::text as="span" size="sm" family="mono">{{ $s->submission_no }}</x-wirekit::text></x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $s->created_at->format('d/m/Y H:i') }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $s->date_from?->format('d/m/Y') ?? 'ทั้งหมด' }} – {{ $s->date_to?->format('d/m/Y') ?? 'ปัจจุบัน' }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ $s->item_count }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ number_format($s->total_amount, 2) }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $s->creator->name }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            <x-wirekit::row gap="xs">
                                <x-wirekit::button size="sm" surface="soft" :href="route('admin.finance.download', [$s, 'pdf'])">PDF</x-wirekit::button>
                                <x-wirekit::button size="sm" intent="success" surface="soft" :href="route('admin.finance.download', [$s, 'xlsx'])">Excel</x-wirekit::button>
                                <x-wirekit::button size="sm" intent="neutral" surface="soft" :href="route('admin.finance.download', [$s, 'zip'])">ZIP สลิป</x-wirekit::button>
                            </x-wirekit::row>
                        </x-wirekit::table.td>
                    </x-wirekit::table.row>
                @empty
                    <x-wirekit::table.row><x-wirekit::table.td colspan="7"><x-wirekit::text as="span" size="sm" intent="muted">ยังไม่มีรายงาน</x-wirekit::text></x-wirekit::table.td></x-wirekit::table.row>
                @endforelse
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>
</x-wirekit::stack>
