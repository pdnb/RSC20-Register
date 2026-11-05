<!DOCTYPE html>
<html lang="th">
    <head>
        @include('layouts.head')
    </head>
    <body class="flex min-h-screen flex-col bg-(--color-wk-bg-muted) text-(--color-wk-text) antialiased">
        <x-wirekit::stack gap="lg" align="center" justify="center" class="flex-1 px-4 py-10">
            <x-wirekit::stack gap="xs" align="center" class="text-center">
                <x-wirekit::image :src="asset('images/logo.webp')" alt="สมาพันธ์นักศึกษามหาวิทยาลัยราชภัฏแห่งประเทศไทย ครั้งที่ 20" loading="eager" width="160" height="160" class="size-40" />
                <x-wirekit::heading level="1" size="lg">โครงการประชุมสัมมนาสมาพันธ์นักศึกษา<br class="sm:hidden">มหาวิทยาลัยราชภัฏแห่งประเทศไทย ครั้งที่ 20</x-wirekit::heading>
                <x-wirekit::text size="sm" intent="muted">ณ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี</x-wirekit::text>
            </x-wirekit::stack>

            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </x-wirekit::stack>

        @include('layouts.footer')

        <x-wirekit::toast-region />
        @wirekitScripts
        @livewireScripts
    </body>
</html>
