<div class="mx-auto max-w-xl space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold text-slate-900">เช็กอินหน้างาน</h1>
        <x-wirekit::badge intent="primary">เช็กอินแล้ว {{ number_format($stats['checked']) }} / {{ number_format($stats['total']) }}</x-wirekit::badge>
    </div>

    {{-- Camera --}}
    <div wire:ignore x-data="{ on: false, scanner: null }" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div id="qr-reader" class="aspect-square w-full bg-slate-900" x-show="on"></div>
        <div class="flex gap-2 p-3">
            <x-wirekit::button class="flex-1" x-show="!on"
                x-on:click="on = true; $nextTick(() => scanner = window.startCheckinScanner('qr-reader', (t) => $wire.scanned(t)))">เปิดกล้องสแกน QR</x-wirekit::button>
            <x-wirekit::button class="flex-1" intent="neutral" surface="outline" x-show="on" x-cloak
                x-on:click="scanner && scanner.stop(); on = false">ปิดกล้อง</x-wirekit::button>
        </div>
    </div>

    {{-- Manual search --}}
    <div class="relative">
        <x-wirekit::input wire:model.live.debounce.300ms="search" label="ค้นหาด้วยชื่อ / เบอร์โทร / รหัส" placeholder="พิมพ์อย่างน้อย 2 ตัวอักษร" clearable />
        @if ($matches->isNotEmpty())
            <div class="absolute z-10 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                @foreach ($matches as $m)
                    <button type="button" wire:click="select({{ $m->id }})" class="block w-full border-b border-slate-100 px-4 py-2 text-left text-sm hover:bg-blue-50">
                        <div class="font-medium">{{ $m->displayName() }} <span class="font-mono text-xs text-slate-400">{{ $m->code }}</span></div>
                        <div class="text-xs text-slate-500">{{ $m->group->university->name }} · {{ $m->mobile_phone }}</div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if ($result === 'notfound')
        <x-wirekit::callout intent="danger">ไม่พบ QR นี้ในระบบ</x-wirekit::callout>
    @endif

    @if ($participant)
        @php($group = $participant->group)
        <x-wirekit::card wire:key="card-{{ $participant->id }}">
            <x-wirekit::card.body class="space-y-4">
                <div class="flex gap-4">
                    @if ($participant->badge_photo_path)
                        <img src="{{ route('files.badge', $participant) }}" class="size-24 rounded-xl object-cover" alt="">
                    @else
                        <div class="flex size-24 items-center justify-center rounded-xl bg-slate-100 text-xs text-slate-400">ไม่มีรูป</div>
                    @endif
                    <div class="min-w-0">
                        <div class="text-lg font-semibold text-slate-900">{{ $participant->displayName() }}</div>
                        <div class="text-sm text-slate-600">{{ $participant->type->name_th }} · {{ $participant->position }}</div>
                        <div class="text-sm text-slate-600">{{ $group->university->name }}</div>
                        <div class="font-mono text-xs text-blue-700">{{ $participant->code }}</div>
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">ไซซ์เสื้อ</dt><dd class="text-xl font-semibold">{{ $participant->shirt_size }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs text-slate-500">อาหาร</dt><dd class="font-medium">{{ $participant->food_type }}</dd></div>
                    <div @class(['col-span-2 rounded-lg p-3', 'bg-red-50 text-red-700' => $participant->food_allergy, 'bg-slate-50' => ! $participant->food_allergy])>
                        <dt class="text-xs opacity-70">อาหารที่แพ้</dt><dd class="font-medium">{{ $participant->food_allergy ?: 'ไม่มี' }}</dd>
                    </div>
                    <div class="col-span-2 flex items-center justify-between rounded-lg bg-slate-50 p-3">
                        <div><dt class="text-xs text-slate-500">การชำระเงิน (ทั้งกลุ่ม {{ $group->participants->count() }} คน)</dt>
                            <dd><x-wirekit::badge :intent="$group->payment_status->intent()">{{ $group->payment_status->label() }}</x-wirekit::badge></dd></div>
                        <div class="text-right font-semibold">{{ number_format($group->total_amount, 2) }} ฿</div>
                    </div>
                </dl>

                @unless ($group->isPaid())
                    <x-wirekit::callout intent="warning">
                        กลุ่มนี้ยังไม่ได้ชำระเงิน{{ $group->payment_status->value === 'submitted' ? ' (สลิปรอตรวจ)' : '' }}
                        <div class="mt-2">
                            <x-wirekit::button size="sm" intent="warning" wire:click="receiveOnsite"
                                wire:confirm="ยืนยันรับเงินหน้างาน {{ number_format($group->total_amount, 2) }} บาท จาก {{ $group->university->name }}?">
                                รับเงินหน้างานแล้ว {{ number_format($group->total_amount, 2) }} บาท
                            </x-wirekit::button>
                        </div>
                    </x-wirekit::callout>
                @endunless

                @if ($participant->checked_in_at)
                    <x-wirekit::callout intent="{{ $result === 'ok' ? 'success' : 'info' }}">
                        {{ $result === 'ok' ? 'เช็กอินสำเร็จ' : 'เช็กอินไปแล้ว' }} เมื่อ {{ $participant->checked_in_at->format('d/m/Y H:i') }}
                        โดย {{ $participant->checkedInBy?->name }}
                    </x-wirekit::callout>
                @else
                    <x-wirekit::button size="lg" class="w-full" wire:click="checkIn">ยืนยันเช็กอิน</x-wirekit::button>
                @endif
            </x-wirekit::card.body>
        </x-wirekit::card>
    @endif

    @assets
        @vite('resources/js/scanner.js')
    @endassets
</div>
