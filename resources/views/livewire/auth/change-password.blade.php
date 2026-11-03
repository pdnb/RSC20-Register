<x-wirekit::container max="sm" padding="none">
    <x-wirekit::card>
        <x-wirekit::card.body>
            <x-wirekit::stack gap="md">
                <x-wirekit::heading level="2" size="base">เปลี่ยนรหัสผ่าน</x-wirekit::heading>
                @if (auth()->user()->must_change_password)
                    <x-wirekit::callout intent="info">เข้าสู่ระบบครั้งแรก กรุณาเปลี่ยนรหัสผ่านก่อนใช้งาน</x-wirekit::callout>
                @endif

                <x-wirekit::form wire:submit="save">
                    <x-wirekit::stack gap="md">
                        <x-wirekit::password-input wire:model="current_password" label="รหัสผ่านปัจจุบัน" autocomplete="current-password" required />
                        <x-wirekit::password-input wire:model="password" label="รหัสผ่านใหม่" hint="อย่างน้อย 8 ตัว มีทั้งตัวอักษรและตัวเลข" autocomplete="new-password" required />
                        <x-wirekit::password-input wire:model="password_confirmation" label="ยืนยันรหัสผ่านใหม่" autocomplete="new-password" required />
                        <x-wirekit::button type="submit" class="w-full">บันทึกรหัสผ่าน</x-wirekit::button>
                    </x-wirekit::stack>
                </x-wirekit::form>
            </x-wirekit::stack>
        </x-wirekit::card.body>
    </x-wirekit::card>
</x-wirekit::container>
