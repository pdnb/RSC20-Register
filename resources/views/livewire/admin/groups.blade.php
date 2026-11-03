<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        @foreach (['submitted' => 'รอตรวจสลิป', 'pending' => 'รอชำระ', 'rejected' => 'สลิปไม่ผ่าน', 'approved' => 'อนุมัติแล้ว', 'onsite_paid' => 'ชำระหน้างานแล้ว', 'all' => 'ทั้งหมด'] as $value => $label)
            <button type="button" wire:click="$set('status', '{{ $value }}')"
                @class([
                    'rounded-full px-3 py-1.5 text-sm',
                    'bg-blue-700 text-white' => $status === $value,
                    'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $status !== $value,
                ])>
                {{ $label }}
                @if ($value !== 'all')<span class="ml-1 opacity-70">{{ $counts[$value] ?? 0 }}</span>@endif
            </button>
        @endforeach
    </div>

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
                        <x-wirekit::table.td><a href="{{ route('admin.groups.show', $g) }}" class="text-blue-700 hover:underline">{{ $g->university->name }}</a></x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ $g->participants_count }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ number_format($g->total_amount, 2) }}</x-wirekit::table.td>
                        <x-wirekit::table.td>{{ $g->payment_method?->label() ?? '-' }}</x-wirekit::table.td>
                        <x-wirekit::table.td><x-wirekit::badge size="sm" :intent="$g->payment_status->intent()">{{ $g->payment_status->label() }}</x-wirekit::badge></x-wirekit::table.td>
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
                    <x-wirekit::table.row><x-wirekit::table.td colspan="7"><span class="text-slate-500">ไม่มีรายการ</span></x-wirekit::table.td></x-wirekit::table.row>
                @endforelse
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>

    <x-wirekit::modal name="review-slip" size="lg" dismissible="false" aria-label="ตรวจสลิป">
        @if ($reviewing)
            <x-wirekit::modal.header>สลิป · {{ $reviewing->university->name }}</x-wirekit::modal.header>
            <x-wirekit::modal.body class="space-y-4">
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-slate-500">ยอดที่ต้องชำระ</dt><dd class="text-lg font-semibold text-blue-800">{{ number_format($reviewing->total_amount, 2) }} ฿</dd></div>
                    <div><dt class="text-slate-500">วันเวลาที่โอน</dt><dd>{{ $reviewing->transferred_at?->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="text-slate-500">เลขอ้างอิง</dt><dd>{{ $reviewing->slip_reference ?: '-' }}</dd></div>
                    <div><dt class="text-slate-500">สถานะ</dt><dd><x-wirekit::badge size="sm" :intent="$reviewing->payment_status->intent()">{{ $reviewing->payment_status->label() }}</x-wirekit::badge></dd></div>
                </dl>
                <a href="{{ route('files.slip', $reviewing) }}" target="_blank">
                    <img src="{{ route('files.slip', $reviewing) }}?v={{ $reviewing->updated_at->timestamp }}" class="mx-auto max-h-[60vh] rounded-lg border border-slate-200" alt="สลิป">
                </a>
                @if ($reviewing->payment_status->value === 'submitted')
                    <x-wirekit::input wire:model="rejectReason" label="เหตุผล (กรณีไม่ผ่าน)" placeholder="เช่น ยอดเงินไม่ตรง, สลิปไม่ชัด" />
                @endif
            </x-wirekit::modal.body>
            @if ($reviewing->payment_status->value === 'submitted')
                <x-wirekit::modal.footer>
                    <x-wirekit::button intent="danger" surface="outline" wire:click="reject">ไม่ผ่าน</x-wirekit::button>
                    <x-wirekit::button intent="success" wire:click="approve" wire:confirm="อนุมัติสลิปยอด {{ number_format($reviewing->total_amount, 2) }} บาท?">อนุมัติ</x-wirekit::button>
                </x-wirekit::modal.footer>
            @endif
        @endif
    </x-wirekit::modal>
</div>
