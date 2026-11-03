<!DOCTYPE html>
<html lang="th">
    <head>
        @include('layouts.head')
    </head>
    <body class="flex min-h-screen flex-col bg-(--color-wk-bg-muted) text-(--color-wk-text) antialiased">
        {{-- Coordinator layout: white top bar --}}
        <x-wirekit::navbar sticky container max="xl" mobile="scroll">
            <x-slot:brand>
                <x-wirekit::brand :logo="asset('images/logo.webp')" logo-aspect="1/1" size="md" :href="route('coordinator.dashboard')">
                    <x-wirekit::text as="span" size="sm" weight="semibold" leading="tight" class="max-sm:hidden">สัมมนาสมาพันธ์นักศึกษา<br>มหาวิทยาลัยราชภัฏ ครั้งที่ 20</x-wirekit::text>
                </x-wirekit::brand>
            </x-slot:brand>

            @auth
                @foreach ([
                    ['coordinator.dashboard', 'รายชื่อผู้เข้าร่วม'],
                    ['coordinator.payment', 'ชำระเงิน'],
                ] as [$name, $label])
                    <x-wirekit::navbar.item :href="route($name)" :active="request()->routeIs($name)" wire:navigate>{{ $label }}</x-wirekit::navbar.item>
                @endforeach
            @endauth

            @auth
                <x-slot:actions>
                    <x-wirekit::stack gap="none" align="end" class="max-sm:hidden">
                        <x-wirekit::text size="xs" weight="medium">{{ auth()->user()->name }}</x-wirekit::text>
                        <x-wirekit::text size="xs" intent="muted">{{ auth()->user()->university?->name }}</x-wirekit::text>
                    </x-wirekit::stack>
                    <x-wirekit::form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-wirekit::button type="submit" intent="neutral" surface="outline" size="sm">ออกจากระบบ</x-wirekit::button>
                    </x-wirekit::form>
                </x-slot:actions>
            @endauth
        </x-wirekit::navbar>

        <x-wirekit::main container max="xl" padding="md" class="w-full flex-1">
            <x-wirekit::stack gap="md">
                @if (session('status'))
                    <x-wirekit::callout intent="success">{{ session('status') }}</x-wirekit::callout>
                @endif

                {{ $slot }}
            </x-wirekit::stack>
        </x-wirekit::main>

        @include('layouts.footer')

        <x-wirekit::toast-region />
        @wirekitScripts
        @livewireScripts
    </body>
</html>
