<div>
<x-wirekit::card>
    <x-wirekit::card.body>
        <h2 class="mb-1 text-base font-semibold text-slate-900">เข้าสู่ระบบผู้ดูแล / เจ้าหน้าที่</h2>
        <p class="mb-5 text-sm text-slate-500">เข้าได้เฉพาะอีเมลที่ได้รับสิทธิ์จากผู้ดูแลระบบ</p>

        @error('auth0')
            <x-wirekit::callout intent="danger" class="mb-4">{{ $message }}</x-wirekit::callout>
        @enderror

        @if ($configured)
            <x-wirekit::button :href="route('admin.auth0.redirect')" class="w-full">เข้าสู่ระบบด้วย Auth0</x-wirekit::button>
        @else
            <x-wirekit::callout intent="warning">ยังไม่ได้ตั้งค่า Auth0 — กรอก AUTH0_DOMAIN, AUTH0_CLIENT_ID และ AUTH0_CLIENT_SECRET ในไฟล์ .env</x-wirekit::callout>
        @endif
    </x-wirekit::card.body>
</x-wirekit::card>

<p class="mt-4 text-center text-xs text-slate-500">
    <a href="{{ route('login') }}" class="text-blue-700 hover:underline">เข้าสู่ระบบผู้ประสานงานมหาวิทยาลัย</a>
</p>
</div>
