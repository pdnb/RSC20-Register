<x-wirekit::stack gap="lg">
    <x-wirekit::page-header title="QR สำหรับเช็กอินหน้างาน" description="ส่งลิงก์ให้ผู้เข้าร่วมแต่ละคน หรือพิมพ์บัตรทั้งหมดเพื่อนำมาแสดงที่จุดลงทะเบียน" class="print:hidden">
        <x-slot:actions>
            <x-wirekit::button intent="neutral" surface="outline" x-on:click="window.print()">พิมพ์ทั้งหมด</x-wirekit::button>
        </x-slot:actions>
    </x-wirekit::page-header>

    @if ($participants->isEmpty())
        <x-wirekit::callout intent="info">ยังไม่มีรายชื่อผู้เข้าร่วม</x-wirekit::callout>
    @endif

    <x-wirekit::grid cols="1 sm:2 lg:3" gap="md" class="print:grid-cols-3 print:gap-2">
        @foreach ($participants as $p)
            <x-wirekit::card class="break-inside-avoid">
                <x-wirekit::card.body>
                    <x-wirekit::stack gap="xs" align="center" class="text-center">
                        {{-- Server-rendered SVG QR, sized by its box. --}}
                        <div class="w-44 [&_svg]:h-auto [&_svg]:w-full">{!! \App\Support\QrCode::forParticipant($p) !!}</div>
                        <x-wirekit::text weight="semibold">{{ $p->displayName() }}</x-wirekit::text>
                        <x-wirekit::text size="xs" intent="muted">{{ $p->type->name_th }} · {{ $p->position }}</x-wirekit::text>
                        <x-wirekit::text size="xs" intent="muted">{{ $group->university->name }}</x-wirekit::text>
                        <x-wirekit::text size="xs" family="mono" intent="accent">{{ $p->code }}</x-wirekit::text>
                        <x-wirekit::stack gap="none" class="pt-2 print:hidden" x-data="{ copied: false }">
                            <x-wirekit::button size="sm" intent="neutral" surface="soft"
                                x-on:click="navigator.clipboard.writeText('{{ route('ticket', $p->qr_token) }}'); copied = true; setTimeout(() => copied = false, 1500)">
                                <x-wirekit::text as="span" size="inherit" weight="inherit" intent="inherit" x-text="copied ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์บัตร'">คัดลอกลิงก์บัตร</x-wirekit::text>
                            </x-wirekit::button>
                        </x-wirekit::stack>
                    </x-wirekit::stack>
                </x-wirekit::card.body>
            </x-wirekit::card>
        @endforeach
    </x-wirekit::grid>
</x-wirekit::stack>
