<x-wirekit::stack gap="lg">
    <x-wirekit::page-header title="ชำระค่าลงทะเบียน" :description="$group->university->name">
        <x-slot:actions>
            <x-wirekit::badge :intent="$group->payment_status->intent()">{{ $group->payment_status->label() }}</x-wirekit::badge>
        </x-slot:actions>
    </x-wirekit::page-header>

    @if ($group->reject_reason && ! $group->isPaid())
        <x-wirekit::callout intent="danger">{{ $group->reject_reason }}</x-wirekit::callout>
    @endif

    <x-wirekit::grid cols="1 lg:5" gap="lg">
        {{-- Breakdown --}}
        <x-wirekit::card class="lg:col-span-3">
            <x-wirekit::card.header><x-wirekit::heading level="2" size="base">สรุปยอดค่าลงทะเบียน</x-wirekit::heading></x-wirekit::card.header>
            <x-wirekit::table>
                <x-wirekit::table.head>
                    <x-wirekit::table.row>
                        <x-wirekit::table.th>ประเภท</x-wirekit::table.th>
                        <x-wirekit::table.th align="right">จำนวน (คน)</x-wirekit::table.th>
                        <x-wirekit::table.th align="right">ค่าลงทะเบียน/คน</x-wirekit::table.th>
                        <x-wirekit::table.th align="right">รวม (บาท)</x-wirekit::table.th>
                    </x-wirekit::table.row>
                </x-wirekit::table.head>
                <x-wirekit::table.body>
                    @php($boardGameOnlyCount = $summary['count'] - $summary['lines']->sum('count'))
                    @forelse ($summary['lines'] as $line)
                        <x-wirekit::table.row>
                            <x-wirekit::table.td>{{ $line['type'] }}</x-wirekit::table.td>
                            <x-wirekit::table.td align="right">{{ $line['count'] }}</x-wirekit::table.td>
                            <x-wirekit::table.td align="right">{{ number_format($line['fee'], 2) }}</x-wirekit::table.td>
                            <x-wirekit::table.td align="right">{{ number_format($line['subtotal'], 2) }}</x-wirekit::table.td>
                        </x-wirekit::table.row>
                    @empty
                        @if ($boardGameOnlyCount === 0)
                        <x-wirekit::table.row>
                            <x-wirekit::table.td colspan="4">
                                <x-wirekit::text as="span" size="sm" intent="muted">ยังไม่มีรายชื่อ <x-wirekit::link :href="route('coordinator.dashboard')">เพิ่มรายชื่อ</x-wirekit::link></x-wirekit::text>
                            </x-wirekit::table.td>
                        </x-wirekit::table.row>
                        @endif
                    @endforelse
                    @if ($boardGameOnlyCount > 0)
                        <x-wirekit::table.row>
                            <x-wirekit::table.td>แข่งขันบอร์ดเกมอย่างเดียว</x-wirekit::table.td>
                            <x-wirekit::table.td align="right">{{ $boardGameOnlyCount }}</x-wirekit::table.td>
                            <x-wirekit::table.td align="right">{{ number_format(0, 2) }}</x-wirekit::table.td>
                            <x-wirekit::table.td align="right">{{ number_format(0, 2) }}</x-wirekit::table.td>
                        </x-wirekit::table.row>
                    @endif
                </x-wirekit::table.body>
            </x-wirekit::table>
            <x-wirekit::card.footer class="bg-(--color-wk-bg-subtle)">
                <x-wirekit::row gap="md" align="center" justify="between" class="w-full">
                    <x-wirekit::stack gap="none">
                        <x-wirekit::text size="sm" weight="medium" intent="accent">ยอดรวมที่ต้องชำระ ({{ $summary['count'] }} คน)</x-wirekit::text>
                        <x-wirekit::text size="xs" intent="accent">({{ \App\Support\ThaiBaht::text($summary['total']) }})</x-wirekit::text>
                    </x-wirekit::stack>
                    <x-wirekit::text size="xl" weight="bold" intent="accent" tabular class="text-2xl">{{ number_format($summary['total'], 2) }} ฿</x-wirekit::text>
                </x-wirekit::row>
            </x-wirekit::card.footer>
        </x-wirekit::card>

        {{-- Payment method --}}
        <x-wirekit::stack gap="md" class="lg:col-span-2">
            @if ($group->isPaid())
                <x-wirekit::callout intent="success" title="ชำระเงินเรียบร้อย">
                    {{ $group->payment_status->label() }} เมื่อ {{ $group->paidAt()?->format('d/m/Y H:i') }}
                </x-wirekit::callout>
            @elseif ($summary['count'] > 0 && $summary['total'] <= 0)
                <x-wirekit::callout intent="info" title="ไม่มีค่าลงทะเบียน">
                    ผู้เข้าร่วมทั้งหมดลงทะเบียนแข่งขันบอร์ดเกมอย่างเดียว จึงไม่ต้องชำระค่าลงทะเบียน
                </x-wirekit::callout>
            @elseif ($summary['count'] > 0)
                <x-wirekit::card>
                    <x-wirekit::card.body>
                        <x-wirekit::stack gap="md">
                            <x-wirekit::text size="sm" weight="medium">เลือกวิธีชำระเงิน</x-wirekit::text>
                            <x-wirekit::grid cols="2" gap="sm" role="radiogroup" aria-label="เลือกวิธีชำระเงิน">
                                @foreach (['transfer' => 'โอนเงิน + แนบสลิป', 'onsite' => 'ชำระหน้างาน'] as $value => $label)
                                    <x-wirekit::radio variant="card" name="method" wire:model.live="method" :value="$value" :label="$label" />
                                @endforeach
                            </x-wirekit::grid>

                            @if ($method === 'transfer')
                                @if ($bank['account_no'])
                                    <x-wirekit::card surface="flat" class="bg-(--color-wk-bg-subtle)">
                                        <x-wirekit::card.body>
                                            <x-wirekit::stack gap="none">
                                                <x-wirekit::text size="sm" intent="muted">โอนเข้าบัญชี</x-wirekit::text>
                                                <x-wirekit::text size="sm" weight="medium">{{ $bank['name'] }}</x-wirekit::text>
                                                <x-wirekit::text family="mono" weight="semibold" intent="accent">{{ $bank['account_no'] }}</x-wirekit::text>
                                                <x-wirekit::text size="sm">{{ $bank['account_name'] }}</x-wirekit::text>
                                            </x-wirekit::stack>
                                        </x-wirekit::card.body>
                                    </x-wirekit::card>
                                @endif

                                <x-wirekit::form wire:submit="submitSlip">
                                    <x-wirekit::stack gap="md">
                                        <x-wirekit::file-upload wire:model="slip" accept="image/jpeg,image/png" label="สลิปการโอนเงิน (jpg/png ไม่เกิน 5MB)" :error="$errors->first('slip')" required />
                                        <x-wirekit::datetime-picker placeholder="เลือกวันและเวลา" wire:model="transferred_at" label="วันเวลาที่โอน" max="now" time="input" time-format="24-hour" :error="$errors->first('transferred_at')" required />
                                        <x-wirekit::input wire:model="slip_reference" label="เลขอ้างอิงในสลิป (ถ้ามี)" />
                                        <x-wirekit::button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="slip,submitSlip">
                                            {{ $group->slip_path ? 'ส่งสลิปใหม่' : 'ส่งสลิป' }} ยอด {{ number_format($summary['total'], 2) }} บาท
                                        </x-wirekit::button>
                                    </x-wirekit::stack>
                                </x-wirekit::form>
                            @else
                                <x-wirekit::text size="sm" intent="muted">ชำระเงินสดหรือโอนที่จุดลงทะเบียนในวันงาน ยอด <x-wirekit::text as="strong" size="inherit" weight="semibold" intent="default">{{ number_format($summary['total'], 2) }} บาท</x-wirekit::text> เจ้าหน้าที่จะบันทึกการรับเงินตอนเช็กอิน</x-wirekit::text>
                                <x-wirekit::button class="w-full" wire:click="chooseOnsite" :disabled="$group->payment_method?->value === 'onsite'">
                                    {{ $group->payment_method?->value === 'onsite' ? 'เลือกชำระหน้างานแล้ว' : 'ยืนยันชำระหน้างาน' }}
                                </x-wirekit::button>
                            @endif
                        </x-wirekit::stack>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @endif

            @if ($group->slip_path)
                <x-wirekit::card>
                    <x-wirekit::card.body>
                        <x-wirekit::stack gap="sm">
                            <x-wirekit::text size="sm" weight="medium">สลิปที่ส่งล่าสุด · {{ $group->slip_submitted_at?->format('d/m/Y H:i') }}</x-wirekit::text>
                            <x-wirekit::link :href="route('files.slip', $group)" target="_blank" :announce-new-tab="false" underline="none" class="block w-fit">
                                <x-wirekit::image :src="route('files.slip', $group).'?v='.$group->updated_at->timestamp" alt="สลิป" rounded class="max-h-72 w-fit border border-(--color-wk-border)" />
                            </x-wirekit::link>
                        </x-wirekit::stack>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @endif
        </x-wirekit::stack>
    </x-wirekit::grid>
</x-wirekit::stack>
