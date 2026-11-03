<!DOCTYPE html>
<html lang="th">
    <head>
        @include('layouts.head')
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <div class="mb-6 text-center">
                <img src="{{ asset('images/logo.webp') }}" alt="สมาพันธ์นักศึกษามหาวิทยาลัยราชภัฏแห่งประเทศไทย ครั้งที่ 20" class="mx-auto mb-3 size-40 object-contain">
                <h1 class="text-lg font-semibold text-slate-900">การอบรมเชิงปฏิบัติการ : สัมมนาสมาพันธ์นักศึกษา<br class="sm:hidden">มหาวิทยาลัยราชภัฏแห่งประเทศไทย ครั้งที่ 20</h1>
                <p class="mt-1 text-sm text-slate-500">ณ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี</p>
            </div>

            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>

        <x-wirekit::toast-region />
        @wirekitScripts
        @livewireScripts
    </body>
</html>
