<x-wirekit::stack gap="md">
    @php
        $statusOptions = collect(['submitted' => 'รอตรวจสลิป', 'pending' => 'รอชำระ', 'rejected' => 'สลิปไม่ผ่าน', 'approved' => 'อนุมัติแล้ว', 'onsite_paid' => 'ชำระหน้างานแล้ว', 'all' => 'ทั้งหมด'])
            ->map(fn (string $label, string $value): string => $value === 'all' ? $label : $label.' ('.($counts[$value] ?? 0).')')
            ->all();
    @endphp
    <x-wirekit::segmented-control wire:model.live="status" :options="$statusOptions" :value="$status" size="sm" aria-label="กรองตามสถานะ" />

    <x-wirekit::card>
        <x-wirekit::table hoverable stack-below="md">
            <x-wirekit::table.head>
                <x-wirekit::table.row>
                    <x-wirekit::table.th>มหาวิทยาลัย</x-wirekit::table.th>
                    <x-wirekit::table.th align="right">คน</x-wirekit::table.th>
                    <x-wirekit::table.th align="right">ยอดเงิน</x-wirekit::table.th>
                    <x-wirekit::table.th>ช่องทาง</x-wirekit::table.th>
                    <x-wirekit::table.th>สถานะ</x-wirekit::table.th>
                    <x-wirekit::table.th>ส่งสลิป</x-wirekit::table.th>
                    <x-wirekit::table.th></x-wirekit::table.th>
                </x-wirekit::table.row>
            </x-wirekit::table.head>
            <x-wirekit::table.body>
                @forelse ($groups as $g)
                    <x-wirekit::table.row wire:key="g-{{ $g->id }}">
                        <x-wirekit::table.td><x-wirekit::link :href="route('admin.groups.show', $g)" underline="hover">{{ $g->university->name }}</x-wirekit::link></x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ $g->participants_count }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ number_format($g->total_amount, 2) }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $g->payment_method?->label() ?? '-' }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            <x-wirekit::row gap="xs" wrap>
                                <x-wirekit::badge size="sm" :intent="$g->payment_status->intent()">{{ $g->payment_status->label() }}</x-wirekit::badge>
                                @if ($g->isEditUnlocked())
                                    <x-wirekit::badge size="sm" intent="warning">ปลดล็อคแก้ไข</x-wirekit::badge>
                                @endif
                            </x-wirekit::row>
                        </x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $g->slip_submitted_at?->format('d/m/Y H:i') ?? '-' }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">
                            @if ($g->slip_path)
                                <x-wirekit::button size="sm" :intent="$g->payment_status->value === 'submitted' ? 'primary' : 'neutral'" surface="soft" wire:click="review({{ $g->id }})">
                                    {{ $g->payment_status->value === 'submitted' ? 'ตรวจสลิป' : 'ดูสลิป' }}
                                </x-wirekit::button>
                            @endif
                        </x-wirekit::table.td>
                    </x-wirekit::table.row>
                @empty
                    <x-wirekit::table.row><x-wirekit::table.td colspan="7"><x-wirekit::text as="span" size="sm" intent="muted">ไม่มีรายการ</x-wirekit::text></x-wirekit::table.td></x-wirekit::table.row>
                @endforelse
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>

    <x-wirekit::modal name="review-slip" size="lg" dismissible="false" aria-label="ตรวจสลิป">
        @if ($reviewing)
            <x-wirekit::modal.header>สลิป · {{ $reviewing->university->name }}</x-wirekit::modal.header>
            <x-wirekit::modal.body>
                <x-wirekit::stack gap="md">
                    <x-wirekit::grid cols="2" gap="md">
                        <x-wirekit::stack gap="none">
                            <x-wirekit::text size="sm" intent="muted">ยอดที่ต้องชำระ</x-wirekit::text>
                            <x-wirekit::text size="lg" weight="semibold" intent="accent">{{ number_format($reviewing->total_amount, 2) }} ฿</x-wirekit::text>
                        </x-wirekit::stack>
                        <x-wirekit::stack gap="none">
                            <x-wirekit::text size="sm" intent="muted">วันเวลาที่โอน</x-wirekit::text>
                            <x-wirekit::text size="sm">{{ $reviewing->transferred_at?->format('d/m/Y H:i') }}</x-wirekit::text>
                        </x-wirekit::stack>
                        <x-wirekit::stack gap="none">
                            <x-wirekit::text size="sm" intent="muted">เลขอ้างอิง</x-wirekit::text>
                            <x-wirekit::text size="sm">{{ $reviewing->slip_reference ?: '-' }}</x-wirekit::text>
                        </x-wirekit::stack>
                        <x-wirekit::stack gap="none" align="start">
                            <x-wirekit::text size="sm" intent="muted">สถานะ</x-wirekit::text>
                            <x-wirekit::badge size="sm" :intent="$reviewing->payment_status->intent()">{{ $reviewing->payment_status->label() }}</x-wirekit::badge>
                        </x-wirekit::stack>
                    </x-wirekit::grid>
                    <x-wirekit::link :href="route('files.slip', $reviewing)" target="_blank" :announce-new-tab="false" underline="none" class="block">
                        <x-wirekit::image :src="route('files.slip', $reviewing).'?v='.$reviewing->updated_at->timestamp" alt="สลิป" rounded loading="eager" class="mx-auto max-h-[60vh] w-fit border border-(--color-wk-border)" />
                    </x-wirekit::link>
                    @if ($reviewing->payment_status->value === 'submitted')
                        <x-wirekit::input wire:model="rejectReason" label="เหตุผล (กรณีไม่ผ่าน)" placeholder="เช่น ยอดเงินไม่ตรง, สลิปไม่ชัด" />
                    @endif
                </x-wirekit::stack>
            </x-wirekit::modal.body>
            @if ($reviewing->payment_status->value === 'submitted')
                <x-wirekit::modal.footer>
                    <x-wirekit::button intent="danger" surface="outline" wire:click="reject">ไม่ผ่าน</x-wirekit::button>
                    <x-wirekit::button intent="success" wire:click="approve" wire:confirm="อนุมัติสลิปยอด {{ number_format($reviewing->total_amount, 2) }} บาท?">อนุมัติ</x-wirekit::button>
                </x-wirekit::modal.footer>
            @endif
        @endif
    </x-wirekit::modal>
</x-wirekit::stack>
