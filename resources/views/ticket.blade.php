<x-layouts.guest-page title="บัตรเข้าร่วมงาน">
    <x-wirekit::card>
        <x-wirekit::card.body>
            <x-wirekit::stack gap="xs" align="center" class="text-center">
                <x-wirekit::text size="xs" weight="medium" intent="accent" class="uppercase tracking-wide">บัตรเข้าร่วมงาน · RSC20</x-wirekit::text>
                {{-- Server-rendered SVG QR, sized by its box. --}}
                <div class="my-3 w-64 [&_svg]:h-auto [&_svg]:w-full">{!! $qr !!}</div>
                <x-wirekit::heading level="2" size="lg">{{ $participant->displayName() }}</x-wirekit::heading>
                <x-wirekit::text size="sm" intent="muted">{{ $participant->type->name_th }} · {{ $participant->position }}</x-wirekit::text>
                <x-wirekit::text size="sm" intent="muted">{{ $participant->group->university->name }}</x-wirekit::text>
                <x-wirekit::text size="sm" family="mono" intent="accent">{{ $participant->code }}</x-wirekit::text>
                @if ($participant->checked_in_at)
                    <x-wirekit::badge intent="success" class="mt-2">เช็กอินแล้ว {{ $participant->checked_in_at->format('d/m/Y H:i') }}</x-wirekit::badge>
                @else
                    <x-wirekit::text size="xs" intent="muted" class="pt-2">แสดง QR นี้ที่จุดลงทะเบียนหน้างาน</x-wirekit::text>
                @endif
            </x-wirekit::stack>
        </x-wirekit::card.body>
    </x-wirekit::card>
</x-layouts.guest-page>
