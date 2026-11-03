<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4 print:hidden">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">QR สำหรับเช็กอินหน้างาน</h1>
            <p class="text-sm text-slate-500">ส่งลิงก์ให้ผู้เข้าร่วมแต่ละคน หรือพิมพ์บัตรทั้งหมดเพื่อนำมาแสดงที่จุดลงทะเบียน</p>
        </div>
        <x-wirekit::button intent="neutral" surface="outline" x-on:click="window.print()">พิมพ์ทั้งหมด</x-wirekit::button>
    </div>

    @if ($participants->isEmpty())
        <x-wirekit::callout intent="info">ยังไม่มีรายชื่อผู้เข้าร่วม</x-wirekit::callout>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 print:grid-cols-3 print:gap-2">
        @foreach ($participants as $p)
            <div class="break-inside-avoid rounded-xl border border-slate-200 bg-white p-4 text-center">
                <div class="mx-auto w-44 [&_svg]:h-auto [&_svg]:w-full">{!! \App\Support\QrCode::forParticipant($p) !!}</div>
                <div class="mt-2 font-semibold text-slate-900">{{ $p->displayName() }}</div>
                <div class="text-xs text-slate-500">{{ $p->type->name_th }} · {{ $p->position }}</div>
                <div class="text-xs text-slate-500">{{ $group->university->name }}</div>
                <div class="mt-1 font-mono text-xs text-blue-700">{{ $p->code }}</div>
                <div class="mt-3 print:hidden" x-data="{ copied: false }">
                    <x-wirekit::button size="sm" intent="neutral" surface="soft"
                        x-on:click="navigator.clipboard.writeText('{{ route('ticket', $p->qr_token) }}'); copied = true; setTimeout(() => copied = false, 1500)">
                        <span x-text="copied ? 'คัดลอกแล้ว' : 'คัดลอกลิงก์บัตร'"></span>
                    </x-wirekit::button>
                </div>
            </div>
        @endforeach
    </div>
</div>
