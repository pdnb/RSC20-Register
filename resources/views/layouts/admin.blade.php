<!DOCTYPE html>
<html lang="th">
    <head>
        @include('layouts.head')
    </head>
    <body class="min-h-screen bg-(--color-wk-bg-muted) text-(--color-wk-text) antialiased">
        @php
            $user = auth()->user();
            $links = $user->isAdmin()
                ? [
                    ['admin.dashboard', 'ภาพรวม', 'dashboard'],
                    ['admin.groups', 'ตรวจสอบการชำระเงิน', 'billing'],
                    ['admin.participants', 'ผู้เข้าร่วม', 'users'],
                    ['staff.checkin', 'เช็กอินหน้างาน', 'scan'],
                    ['admin.exports', 'ดาวน์โหลดข้อมูล', 'download'],
                    ['admin.finance', 'นำส่งกองคลัง', 'coins'],
                    ['admin.coordinators', 'บัญชีผู้ประสานงาน', 'user-group'],
                    ['admin.users', 'ผู้ดูแลและเจ้าหน้าที่', 'shield-check'],
                    ['admin.settings', 'ตั้งค่าระบบ', 'settings'],
                ]
                : [['staff.checkin', 'เช็กอินหน้างาน', 'scan']];
        @endphp

        <x-wirekit::app-shell viewport header-placement="content" :sidebar-inset="false" class="bg-(--color-wk-bg-muted)">
            <x-slot:sidebar>
                <x-wirekit::sidebar variant="flush" label="เมนูผู้ดูแล">
                    <x-slot:header>
                        <x-wirekit::brand :logo="asset('images/logo.webp')" logo-aspect="1/1" size="md" name="Admin Console" :href="route('admin.dashboard')" />
                    </x-slot:header>

                    @foreach ($links as [$name, $label, $icon])
                        <x-wirekit::sidebar.item :href="route($name)" :icon="$icon" :active="request()->routeIs($name.'*')" wire:navigate>{{ $label }}</x-wirekit::sidebar.item>
                    @endforeach
                </x-wirekit::sidebar>
            </x-slot:sidebar>

            <x-slot:header>
                <x-wirekit::header class="border-b border-(--color-wk-border) bg-(--color-wk-bg) px-4 py-3">
                    <x-wirekit::sidebar.toggle class="lg:hidden" />
                    <x-wirekit::heading level="1" size="sm" class="truncate">{{ $title ?? '' }}</x-wirekit::heading>
                    <x-wirekit::row gap="md" align="center" class="ml-auto">
                        <x-wirekit::stack gap="none" align="end" class="max-sm:hidden">
                            <x-wirekit::text size="xs" weight="medium">{{ $user->name }}</x-wirekit::text>
                            <x-wirekit::text size="xs" intent="muted">{{ $user->role->label() }}</x-wirekit::text>
                        </x-wirekit::stack>
                        <x-wirekit::form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <x-wirekit::button type="submit" intent="neutral" surface="outline" size="sm">ออกจากระบบ</x-wirekit::button>
                        </x-wirekit::form>
                    </x-wirekit::row>
                </x-wirekit::header>
            </x-slot:header>

            <x-wirekit::main padding="md" max="none">
                <x-wirekit::stack gap="md">
                    @if (session('status'))
                        <x-wirekit::callout intent="success">{{ session('status') }}</x-wirekit::callout>
                    @endif

                    {{ $slot }}
                </x-wirekit::stack>

                @include('layouts.footer')
            </x-wirekit::main>
        </x-wirekit::app-shell>

        <x-wirekit::toast-region />
        @wirekitScripts
        @livewireScripts
    </body>
</html>
