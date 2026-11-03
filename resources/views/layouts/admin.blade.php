<!DOCTYPE html>
<html lang="th">
    <head>
        @include('layouts.head')
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-800 antialiased" x-data="{ nav: false }">
        @php
            $user = auth()->user();
            $links = $user->isAdmin()
                ? [
                    ['admin.dashboard', 'ภาพรวม'],
                    ['admin.groups', 'ตรวจสอบการชำระเงิน'],
                    ['admin.participants', 'ผู้เข้าร่วม'],
                    ['staff.checkin', 'เช็กอินหน้างาน'],
                    ['admin.exports', 'ดาวน์โหลดข้อมูล'],
                    ['admin.finance', 'นำส่งกองคลัง'],
                    ['admin.coordinators', 'บัญชีผู้ประสานงาน'],
                    ['admin.users', 'ผู้ดูแลและเจ้าหน้าที่'],
                    ['admin.settings', 'ตั้งค่าระบบ'],
                ]
                : [['staff.checkin', 'เช็กอินหน้างาน']];
        @endphp

        <div class="flex min-h-screen">
            {{-- White sidebar with blue accents --}}
            <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full border-r border-slate-200 bg-white text-slate-600 transition-transform lg:static lg:translate-x-0"
                   :class="nav && 'translate-x-0'">
                <div class="flex items-center gap-2 border-b border-slate-200 px-5 py-4">
                    <img src="{{ asset('images/logo.webp') }}" alt="" class="size-11 shrink-0 rounded-xl bg-white object-contain">
                    <div class="text-sm font-semibold leading-tight text-blue-700">Admin Console</div>
                </div>
                <nav class="space-y-0.5 p-3 text-sm">
                    @foreach ($links as [$name, $label])
                        <a href="{{ route($name) }}" wire:navigate
                           @class([
                               'block rounded-lg border-l-4 px-3 py-2',
                               'border-gold-500 bg-blue-50 font-medium text-blue-700' => request()->routeIs($name.'*'),
                               'border-transparent hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($name.'*'),
                           ])>{{ $label }}</a>
                    @endforeach
                </nav>
            </aside>
            <div class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" x-show="nav" x-cloak @click="nav = false"></div>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3">
                    <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="nav = true" aria-label="เปิดเมนู">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="truncate text-sm font-semibold text-slate-900">{{ $title ?? '' }}</div>
                    <div class="ml-auto flex items-center gap-3">
                        <div class="hidden text-right text-xs sm:block">
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-slate-500">{{ $user->role->label() }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <x-wirekit::button type="submit" intent="neutral" surface="outline" size="sm">ออกจากระบบ</x-wirekit::button>
                        </form>
                    </div>
                </header>

                <main class="flex-1 p-4 lg:p-6">
                    @if (session('status'))
                        <x-wirekit::callout intent="success" class="mb-4">{{ session('status') }}</x-wirekit::callout>
                    @endif

                    {{ $slot }}
                </main>

                @include('layouts.footer')
            </div>
        </div>

        <x-wirekit::toast-region />
        @wirekitScripts
        @livewireScripts
    </body>
</html>
