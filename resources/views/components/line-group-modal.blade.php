{{-- Invites coordinators to the LINE group: opens by itself once per login session, afterwards only from the navbar button. --}}
@php
    $url = \App\Models\Setting::get('line_group_url');
    $imageUrl = \App\Models\Setting::lineQrUrl();
    $autoOpen = ! session()->has('line_group_modal_shown');
    session()->put('line_group_modal_shown', true);
@endphp

<x-wirekit::modal name="line-group" size="sm">
    <x-wirekit::modal.header>เข้ากลุ่ม LINE ผู้ประสานงาน</x-wirekit::modal.header>
    <x-wirekit::modal.body>
        <x-wirekit::stack gap="md" align="center" class="text-center">
            <x-wirekit::text size="sm">สแกน QR Code เพื่อรับข่าวสารและสอบถามการลงทะเบียน</x-wirekit::text>

            {{-- The white plate stays in dark mode too: scanners need a light quiet zone around the code. --}}
            <div class="w-56 rounded-lg bg-white p-2 [&_svg]:h-auto [&_svg]:w-full">
                @if ($imageUrl)
                    <x-wirekit::image :src="$imageUrl" alt="QR Code กลุ่ม LINE ผู้ประสานงาน" loading="eager" class="w-full" />
                @else
                    {!! \App\Support\QrCode::svg($url, 280) !!}
                @endif
            </div>
        </x-wirekit::stack>
    </x-wirekit::modal.body>
    <x-wirekit::modal.footer>
        <x-wirekit::modal.close><x-wirekit::button intent="neutral" surface="ghost">ปิด</x-wirekit::button></x-wirekit::modal.close>
        @if (filled($url))
            <x-wirekit::button intent="success" :href="$url" target="_blank" rel="noopener noreferrer">เข้าร่วมกลุ่ม LINE</x-wirekit::button>
        @endif
    </x-wirekit::modal.footer>
</x-wirekit::modal>

@if ($autoOpen)
    {{-- Removes itself, so the copy wire:navigate caches for the Back button cannot open the modal again. --}}
    <div hidden x-data x-init="$nextTick(() => { $dispatch('wirekit-modal-show', { name: 'line-group' }); $el.remove() })"></div>
@endif
