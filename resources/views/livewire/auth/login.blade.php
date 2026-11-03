<x-wirekit::stack gap="md">
    <x-wirekit::card>
        <x-wirekit::card.body>
            <x-wirekit::stack gap="lg">
                <x-wirekit::heading level="2" size="base">เข้าสู่ระบบผู้ประสานงานมหาวิทยาลัย</x-wirekit::heading>

                <x-wirekit::form wire:submit="login">
                    <x-wirekit::stack gap="md">
                        <x-wirekit::input wire:model="username" label="ชื่อผู้ใช้" autocomplete="username" required autofocus />
                        <x-wirekit::password-input wire:model="password" label="รหัสผ่าน" autocomplete="current-password" required />
                        <x-wirekit::checkbox wire:model="remember" label="จดจำการเข้าสู่ระบบ" />
                        <x-wirekit::button type="submit" class="w-full">เข้าสู่ระบบ</x-wirekit::button>
                    </x-wirekit::stack>
                </x-wirekit::form>
            </x-wirekit::stack>
        </x-wirekit::card.body>
    </x-wirekit::card>

    <x-wirekit::text size="xs" intent="muted" align="center">
        ยังไม่มีบัญชี? ติดต่อผู้ดูแลระบบ มหาวิทยาลัยราชภัฏสุราษฎร์ธานี ·
        <x-wirekit::link :href="route('admin.login')" underline="hover">สำหรับผู้ดูแล</x-wirekit::link>
    </x-wirekit::text>
</x-wirekit::stack>
