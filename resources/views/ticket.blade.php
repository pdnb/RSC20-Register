<x-layouts.guest-page title="บัตรเข้าร่วมงาน">
    <x-wirekit::card>
        <x-wirekit::card.body class="text-center">
            <div class="text-xs font-medium uppercase tracking-wide text-blue-700">บัตรเข้าร่วมงาน · RSC20</div>
            <div class="mx-auto my-4 w-64 [&_svg]:h-auto [&_svg]:w-full">{!! $qr !!}</div>
            <div class="text-lg font-semibold text-slate-900">{{ $participant->displayName() }}</div>
            <div class="text-sm text-slate-500">{{ $participant->type->name_th }} · {{ $participant->position }}</div>
            <div class="text-sm text-slate-500">{{ $participant->group->university->name }}</div>
            <div class="mt-2 font-mono text-sm text-blue-700">{{ $participant->code }}</div>
            @if ($participant->checked_in_at)
                <x-wirekit::badge intent="success" class="mt-3">เช็กอินแล้ว {{ $participant->checked_in_at->format('d/m/Y H:i') }}</x-wirekit::badge>
            @else
                <p class="mt-3 text-xs text-slate-500">แสดง QR นี้ที่จุดลงทะเบียนหน้างาน</p>
            @endif
        </x-wirekit::card.body>
    </x-wirekit::card>
</x-layouts.guest-page>
