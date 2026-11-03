<!DOCTYPE html>
<html lang="th">
    <head>
        @include('layouts.head')
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
        {{-- Coordinator layout: white top bar --}}
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                <a href="{{ route('coordinator.dashboard') }}" class="flex items-center gap-2">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-blue-700 text-sm font-bold text-white">20</span>
                    <span class="hidden text-sm font-semibold leading-tight text-slate-900 sm:block">สัมมนาสมาพันธ์นักศึกษา<br>มหาวิทยาลัยราชภัฏ ครั้งที่ 20</span>
                </a>

                @auth
                    <nav class="ml-4 hidden items-center gap-1 text-sm md:flex">
                        @foreach ([
                            ['coordinator.dashboard', 'รายชื่อผู้เข้าร่วม'],
                            ['coordinator.payment', 'ชำระเงิน'],
                            ['coordinator.qr', 'QR เช็กอิน'],
                        ] as [$name, $label])
                            <a href="{{ route($name) }}" wire:navigate
                               @class([
                                   'rounded-lg px-3 py-2 font-medium',
                                   'bg-blue-50 text-blue-700' => request()->routeIs($name),
                                   'text-slate-600 hover:bg-slate-100' => ! request()->routeIs($name),
                               ])>{{ $label }}</a>
                        @endforeach
                    </nav>

                    <div class="ml-auto flex items-center gap-3">
                        <div class="hidden text-right text-xs sm:block">
                            <div class="font-medium text-slate-800">{{ auth()->user()->name }}</div>
                            <div class="text-slate-500">{{ auth()->user()->university?->name }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-wirekit::button type="submit" intent="neutral" surface="outline" size="sm">ออกจากระบบ</x-wirekit::button>
                        </form>
                    </div>
                @endauth
            </div>

            @auth
                <nav class="flex gap-1 overflow-x-auto border-t border-slate-100 px-4 py-2 text-sm md:hidden">
                    <a href="{{ route('coordinator.dashboard') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 {{ request()->routeIs('coordinator.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-600' }}">รายชื่อ</a>
                    <a href="{{ route('coordinator.payment') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 {{ request()->routeIs('coordinator.payment') ? 'bg-blue-50 text-blue-700' : 'text-slate-600' }}">ชำระเงิน</a>
                    <a href="{{ route('coordinator.qr') }}" class="whitespace-nowrap rounded-lg px-3 py-1.5 {{ request()->routeIs('coordinator.qr') ? 'bg-blue-50 text-blue-700' : 'text-slate-600' }}">QR เช็กอิน</a>
                </nav>
            @endauth
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6">
            @if (session('status'))
                <x-wirekit::callout intent="success" class="mb-4">{{ session('status') }}</x-wirekit::callout>
            @endif

            {{ $slot }}
        </main>

        <x-wirekit::toast-region />
        @wirekitScripts
        @livewireScripts
    </body>
</html>
