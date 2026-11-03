<div>
<x-wirekit::card>
    <x-wirekit::card.body>
        <h2 class="mb-1 text-base font-semibold text-slate-900">เข้าสู่ระบบผู้ประสานงานมหาวิทยาลัย</h2>
        <p class="mb-5 text-sm text-slate-500">ใช้ชื่อผู้ใช้และรหัสผ่านที่ได้รับจากผู้ดูแลระบบ</p>

        <form wire:submit="login" class="space-y-4">
            <x-wirekit::input wire:model="username" label="ชื่อผู้ใช้" autocomplete="username" required autofocus />
            <x-wirekit::input wire:model="password" type="password" label="รหัสผ่าน" autocomplete="current-password" required />
            <x-wirekit::checkbox wire:model="remember" label="จดจำการเข้าสู่ระบบ" />
            <x-wirekit::button type="submit" class="w-full">เข้าสู่ระบบ</x-wirekit::button>
        </form>
    </x-wirekit::card.body>
</x-wirekit::card>

<p class="mt-4 text-center text-xs text-slate-500">
    ยังไม่มีบัญชี? ติดต่อผู้ดูแลระบบ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี ·
    <a href="{{ route('admin.login') }}" class="text-blue-700 hover:underline">สำหรับผู้ดูแล</a>
</p>
</div>
