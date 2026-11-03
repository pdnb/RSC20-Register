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
                    ['admin.groups', 'ตรวจสลิป / กลุ่ม'],
                    ['admin.participants', 'ผู้เข้าร่วม'],
                    ['staff.checkin', 'เช็กอินหน้างาน'],
                    ['admin.exports', 'Export Excel'],
                    ['admin.finance', 'รายงานนำส่งกองคลัง'],
                    ['admin.coordinators', 'บัญชีผู้ประสานงาน'],
                    ['admin.users', 'ผู้ดูแล / staff'],
                    ['admin.settings', 'ตั้งค่า / ค่าลงทะเบียน'],
                ]
                : [['staff.checkin', 'เช็กอินหน้างาน']];
        @endphp

        <div class="flex min-h-screen">
            {{-- Dark-blue sidebar --}}
            <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full bg-blue-950 text-blue-100 transition-transform lg:static lg:translate-x-0"
                   :class="nav && 'translate-x-0'">
                <div class="flex items-center gap-2 border-b border-blue-900 px-5 py-4">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-white text-sm font-bold text-blue-800">20</span>
                    <div class="text-sm font-semibold leading-tight text-white">RSC20<br><span class="font-normal text-blue-300">ส่วนผู้ดูแล</span></div>
                </div>
                <nav class="space-y-0.5 p-3 text-sm">
                    @foreach ($links as [$name, $label])
                        <a href="{{ route($name) }}" wire:navigate
                           @class([
                               'block rounded-lg px-3 py-2',
                               'bg-blue-700 font-medium text-white' => request()->routeIs($name.'*'),
                               'hover:bg-blue-900' => ! request()->routeIs($name.'*'),
                           ])>{{ $label }}</a>
                    @endforeach
                </nav>
            </aside>
            <div class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden" x-show="nav" x-cloak @click="nav = false"></div>

            <div class="min-w-0 flex-1">
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

                <main class="p-4 lg:p-6">
                    @if (session('status'))
                        <x-wirekit::callout intent="success" class="mb-4">{{ session('status') }}</x-wirekit::callout>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-wirekit::toast-region />
        @wirekitScripts
        @livewireScripts
    </body>
</html>
