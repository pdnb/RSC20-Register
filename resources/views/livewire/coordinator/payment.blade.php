<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">ชำระค่าลงทะเบียน</h1>
            <p class="text-sm text-slate-500">{{ $group->university->name }}</p>
        </div>
        <x-wirekit::badge :intent="$group->payment_status->intent()">{{ $group->payment_status->label() }}</x-wirekit::badge>
    </div>

    @if ($group->reject_reason && ! $group->isPaid())
        <x-wirekit::callout intent="danger">{{ $group->reject_reason }}</x-wirekit::callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Breakdown --}}
        <x-wirekit::card class="lg:col-span-3">
            <x-wirekit::card.header><h2 class="font-semibold">สรุปยอดค่าลงทะเบียน</h2></x-wirekit::card.header>
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
                    @forelse ($summary['lines'] as $line)
                        <x-wirekit::table.row>
                            <x-wirekit::table.td>{{ $line['type'] }}</x-wirekit::table.td>
                            <x-wirekit::table.td align="right">{{ $line['count'] }}</x-wirekit::table.td>
                            <x-wirekit::table.td align="right">{{ number_format($line['fee'], 2) }}</x-wirekit::table.td>
                            <x-wirekit::table.td align="right">{{ number_format($line['subtotal'], 2) }}</x-wirekit::table.td>
                        </x-wirekit::table.row>
                    @empty
                        <x-wirekit::table.row>
                            <x-wirekit::table.td colspan="4"><span class="text-slate-500">ยังไม่มีรายชื่อ — <a class="text-blue-700 underline" href="{{ route('coordinator.dashboard') }}">เพิ่มรายชื่อ</a></span></x-wirekit::table.td>
                        </x-wirekit::table.row>
                    @endforelse
                </x-wirekit::table.body>
            </x-wirekit::table>
            <div class="flex items-center justify-between border-t border-slate-200 bg-blue-50 px-4 py-4">
                <div>
                    <div class="text-sm font-medium text-blue-800">ยอดรวมที่ต้องชำระ ({{ $summary['count'] }} คน)</div>
                    <div class="text-xs text-blue-700">({{ \App\Support\ThaiBaht::text($summary['total']) }})</div>
                </div>
                <div class="text-2xl font-bold text-blue-800">{{ number_format($summary['total'], 2) }} ฿</div>
            </div>
        </x-wirekit::card>

        {{-- Payment method --}}
        <div class="space-y-4 lg:col-span-2">
            @if ($group->isPaid())
                <x-wirekit::callout intent="success" title="ชำระเงินเรียบร้อย">
                    {{ $group->payment_status->label() }} เมื่อ {{ $group->paidAt()?->format('d/m/Y H:i') }}
                </x-wirekit::callout>
            @elseif ($summary['count'] > 0)
                <x-wirekit::card>
                    <x-wirekit::card.body class="space-y-4">
                        <div class="text-sm font-medium text-slate-800">เลือกวิธีชำระเงิน</div>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['transfer' => 'โอนเงิน + แนบสลิป', 'onsite' => 'ชำระหน้างาน'] as $value => $label)
                                <label @class([
                                    'cursor-pointer rounded-xl border p-3 text-center text-sm',
                                    'border-blue-600 bg-blue-50 font-medium text-blue-800' => $method === $value,
                                    'border-slate-200 text-slate-600' => $method !== $value,
                                ])>
                                    <input type="radio" class="sr-only" wire:model.live="method" value="{{ $value }}">{{ $label }}
                                </label>
                            @endforeach
                        </div>

                        @if ($method === 'transfer')
                            @if ($bank['account_no'])
                                <div class="rounded-lg bg-slate-50 p-3 text-sm">
                                    <div class="text-slate-500">โอนเข้าบัญชี</div>
                                    <div class="font-medium">{{ $bank['name'] }}</div>
                                    <div class="font-mono text-base font-semibold text-blue-800">{{ $bank['account_no'] }}</div>
                                    <div>{{ $bank['account_name'] }}</div>
                                </div>
                            @endif

                            <form wire:submit="submitSlip" class="space-y-3">
                                <div>
                                    <div class="mb-1 text-sm font-medium text-slate-800">สลิปการโอนเงิน (jpg/png ไม่เกิน 5MB)</div>
                                    <input type="file" wire:model="slip" accept="image/jpeg,image/png"
                                        class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700">
                                    <div wire:loading wire:target="slip" class="mt-1 text-xs text-slate-500">กำลังอัปโหลด…</div>
                                    @error('slip')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <x-wirekit::input wire:model="transferred_at" type="datetime-local" label="วันเวลาที่โอน" required />
                                <x-wirekit::input wire:model="slip_reference" label="เลขอ้างอิงในสลิป (ถ้ามี)" />
                                <x-wirekit::button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="slip,submitSlip">
                                    {{ $group->slip_path ? 'ส่งสลิปใหม่' : 'ส่งสลิป' }} ยอด {{ number_format($summary['total'], 2) }} บาท
                                </x-wirekit::button>
                            </form>
                        @else
                            <p class="text-sm text-slate-600">ชำระเงินสดหรือโอนที่จุดลงทะเบียนในวันงาน ยอด <b>{{ number_format($summary['total'], 2) }} บาท</b> เจ้าหน้าที่จะบันทึกการรับเงินตอนเช็กอิน</p>
                            <x-wirekit::button class="w-full" wire:click="chooseOnsite" :disabled="$group->payment_method?->value === 'onsite'">
                                {{ $group->payment_method?->value === 'onsite' ? 'เลือกชำระหน้างานแล้ว' : 'ยืนยันชำระหน้างาน' }}
                            </x-wirekit::button>
                        @endif
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @endif

            @if ($group->slip_path)
                <x-wirekit::card>
                    <x-wirekit::card.body>
                        <div class="mb-2 text-sm font-medium">สลิปที่ส่งล่าสุด · {{ $group->slip_submitted_at?->format('d/m/Y H:i') }}</div>
                        <a href="{{ route('files.slip', $group) }}" target="_blank">
                            <img src="{{ route('files.slip', $group) }}?v={{ $group->updated_at->timestamp }}" class="max-h-72 rounded-lg border border-slate-200" alt="สลิป">
                        </a>
                    </x-wirekit::card.body>
                </x-wirekit::card>
            @endif
        </div>
    </div>
</div>
