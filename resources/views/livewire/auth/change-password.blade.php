<div class="mx-auto max-w-md">
    <x-wirekit::card>
        <x-wirekit::card.body>
            <h2 class="mb-1 text-base font-semibold text-slate-900">เปลี่ยนรหัสผ่าน</h2>
            @if (auth()->user()->must_change_password)
                <x-wirekit::callout intent="info" class="mb-4">เข้าสู่ระบบครั้งแรก กรุณาเปลี่ยนรหัสผ่านก่อนใช้งาน</x-wirekit::callout>
            @endif

            <form wire:submit="save" class="space-y-4">
                <x-wirekit::input wire:model="current_password" type="password" label="รหัสผ่านปัจจุบัน" autocomplete="current-password" required />
                <x-wirekit::input wire:model="password" type="password" label="รหัสผ่านใหม่" hint="อย่างน้อย 8 ตัว มีทั้งตัวอักษรและตัวเลข" autocomplete="new-password" required />
                <x-wirekit::input wire:model="password_confirmation" type="password" label="ยืนยันรหัสผ่านใหม่" autocomplete="new-password" required />
                <x-wirekit::button type="submit" class="w-full">บันทึกรหัสผ่าน</x-wirekit::button>
            </form>
        </x-wirekit::card.body>
    </x-wirekit::card>
</div>
