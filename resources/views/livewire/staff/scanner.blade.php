<x-wirekit::stack gap="md">
    <x-wirekit::page-header title="เช็กอินหน้างาน" size="lg">
        <x-slot:actions>
            <x-wirekit::badge intent="primary">เช็กอินแล้ว {{ number_format($stats['checked']) }} / {{ number_format($stats['total']) }}</x-wirekit::badge>
        </x-slot:actions>
    </x-wirekit::page-header>

    {{-- Camera --}}
    <x-wirekit::card wire:ignore x-data="{ on: false, scanner: null }">
        <div id="qr-reader" class="mx-auto aspect-square w-full max-w-md bg-(--color-wk-bg-inverse)" x-show="on"></div>
        <x-wirekit::card.body>
            <x-wirekit::button class="w-full" x-show="!on"
                x-on:click="on = true; $nextTick(() => scanner = window.startCheckinScanner('qr-reader', (t) => $wire.scanned(t)))">เปิดกล้องสแกน QR</x-wirekit::button>
            <x-wirekit::button class="w-full" intent="neutral" surface="outline" x-show="on" x-cloak
                x-on:click="scanner && scanner.stop(); on = false">ปิดกล้อง</x-wirekit::button>
        </x-wirekit::card.body>
    </x-wirekit::card>

    {{-- Manual search --}}
    <div class="relative">
        <x-wirekit::input wire:model.live.debounce.300ms="search" label="ค้นหาด้วยชื่อ / เบอร์โทร / รหัส" placeholder="พิมพ์อย่างน้อย 2 ตัวอักษร" clearable />
        @if ($matches->isNotEmpty())
            <x-wirekit::card surface="elevated" class="absolute z-10 mt-1 w-full">
                <x-wirekit::stack gap="none" class="divide-y divide-(--color-wk-border)">
                    @foreach ($matches as $m)
                        <x-wirekit::button intent="neutral" surface="ghost" class="h-auto w-full justify-start rounded-none py-2 text-left" wire:click="select({{ $m->id }})">
                            <x-wirekit::stack gap="none" align="start">
                                <x-wirekit::text as="span" size="sm" weight="medium">{{ $m->displayName() }} <x-wirekit::text as="span" size="xs" family="mono" intent="subtle">{{ $m->code }}</x-wirekit::text></x-wirekit::text>
                                <x-wirekit::text as="span" size="xs" intent="muted">{{ $m->group->university->name }} · {{ $m->mobile_phone }}</x-wirekit::text>
                            </x-wirekit::stack>
                        </x-wirekit::button>
                    @endforeach
                </x-wirekit::stack>
            </x-wirekit::card>
        @endif
    </div>

    @if ($result === 'notfound')
        <x-wirekit::callout intent="danger">ไม่พบ QR นี้ในระบบ</x-wirekit::callout>
    @endif

    @if ($participant)
        @php($group = $participant->group)
        <x-wirekit::card wire:key="card-{{ $participant->id }}">
            <x-wirekit::card.body>
                <x-wirekit::stack gap="md">
                    <x-wirekit::row gap="md" align="start">
                        <div class="size-24 shrink-0 overflow-hidden rounded-xl">
                            @if ($participant->badge_photo_path)
                                <x-wirekit::image :src="route('files.badge', $participant)" alt="" ratio="1/1" loading="eager" />
                            @else
                                <x-wirekit::center class="h-full bg-(--color-wk-bg-muted)">
                                    <x-wirekit::text as="span" size="xs" intent="subtle">ไม่มีรูป</x-wirekit::text>
                                </x-wirekit::center>
                            @endif
                        </div>
                        <x-wirekit::stack gap="none" class="min-w-0">
                            <x-wirekit::heading level="2" size="lg">{{ $participant->displayName() }}</x-wirekit::heading>
                            <x-wirekit::text size="sm" intent="muted">{{ $participant->type->name_th }} · {{ $participant->position }}</x-wirekit::text>
                            <x-wirekit::text size="sm" intent="muted">{{ $group->university->name }}</x-wirekit::text>
                            <x-wirekit::text size="xs" family="mono" intent="accent">{{ $participant->code }}</x-wirekit::text>
                        </x-wirekit::stack>
                    </x-wirekit::row>

                    <x-wirekit::grid cols="2" gap="sm">
                        <x-wirekit::card surface="flat" class="bg-(--color-wk-bg-subtle)">
                            <x-wirekit::card.body>
                                <x-wirekit::text size="xs" intent="muted">ไซซ์เสื้อ</x-wirekit::text>
                                <x-wirekit::text size="xl" weight="semibold">{{ $participant->shirtSizeLabel() }}</x-wirekit::text>
                            </x-wirekit::card.body>
                        </x-wirekit::card>
                        <x-wirekit::card surface="flat" class="bg-(--color-wk-bg-subtle)">
                            <x-wirekit::card.body>
                                <x-wirekit::text size="xs" intent="muted">อาหาร</x-wirekit::text>
                                <x-wirekit::text size="sm" weight="medium">{{ $participant->food_type }}</x-wirekit::text>
                            </x-wirekit::card.body>
                        </x-wirekit::card>
                        @if ($participant->food_allergy)
                            <x-wirekit::callout intent="danger" :icon="false" title="อาหารที่แพ้" class="col-span-2">{{ $participant->food_allergy }}</x-wirekit::callout>
                        @else
                            <x-wirekit::card surface="flat" class="col-span-2 bg-(--color-wk-bg-subtle)">
                                <x-wirekit::card.body>
                                    <x-wirekit::text size="xs" intent="muted">อาหารที่แพ้</x-wirekit::text>
                                    <x-wirekit::text size="sm" weight="medium">ไม่มี</x-wirekit::text>
                                </x-wirekit::card.body>
                            </x-wirekit::card>
                        @endif
                        <x-wirekit::card surface="flat" class="col-span-2 bg-(--color-wk-bg-subtle)">
                            <x-wirekit::card.body>
                                <x-wirekit::row gap="sm" align="center" justify="between">
                                    <x-wirekit::stack gap="xs" align="start">
                                        <x-wirekit::text size="xs" intent="muted">การชำระเงิน (ทั้งกลุ่ม {{ $group->participants->count() }} คน)</x-wirekit::text>
                                        <x-wirekit::badge :intent="$group->payment_status->intent()">{{ $group->payment_status->label() }}</x-wirekit::badge>
                                    </x-wirekit::stack>
                                    <x-wirekit::text size="sm" weight="semibold" tabular>{{ number_format($group->total_amount, 2) }} ฿</x-wirekit::text>
                                </x-wirekit::row>
                            </x-wirekit::card.body>
                        </x-wirekit::card>
                    </x-wirekit::grid>

                    @unless ($group->isPaid())
                        <x-wirekit::callout intent="warning">
                            <x-wirekit::stack gap="sm" align="start">
                                <x-wirekit::text as="span" size="inherit" intent="inherit">กลุ่มนี้ยังไม่ได้ชำระเงิน{{ $group->payment_status->value === 'submitted' ? ' (สลิปรอตรวจ)' : '' }}</x-wirekit::text>
                                <x-wirekit::button size="sm" intent="warning" wire:click="receiveOnsite"
                                    wire:confirm="ยืนยันรับเงินหน้างาน {{ number_format($group->total_amount, 2) }} บาท จาก {{ $group->university->name }}?">
                                    รับเงินหน้างานแล้ว {{ number_format($group->total_amount, 2) }} บาท
                                </x-wirekit::button>
                            </x-wirekit::stack>
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
                </x-wirekit::stack>
            </x-wirekit::card.body>
        </x-wirekit::card>
    @endif

    @assets
        @vite('resources/js/scanner.js')
    @endassets
</x-wirekit::stack>
