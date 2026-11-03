<x-wirekit::stack gap="md">
    <x-wirekit::card>
        <x-wirekit::card.body>
            <x-wirekit::stack gap="md">
                <x-wirekit::stack gap="xs">
                    <x-wirekit::heading level="2" size="base">เข้าสู่ระบบผู้ดูแล / เจ้าหน้าที่ มรส.</x-wirekit::heading>
                    <x-wirekit::text size="sm" intent="muted">เข้าได้เฉพาะอีเมลที่ได้รับสิทธิ์จากผู้ดูแลระบบ</x-wirekit::text>
                </x-wirekit::stack>

                @error('auth0')
                    <x-wirekit::callout intent="danger">{{ $message }}</x-wirekit::callout>
                @enderror

                @if ($configured)
                    <x-wirekit::button :href="route('admin.auth0.redirect')" class="w-full">เข้าสู่ระบบด้วยบัญชีมหาวิทยาลัย</x-wirekit::button>
                @else
                    <x-wirekit::callout intent="warning">ยังไม่ได้ตั้งค่า Auth0</x-wirekit::callout>
                @endif
            </x-wirekit::stack>
        </x-wirekit::card.body>
    </x-wirekit::card>

    <x-wirekit::text size="xs" intent="muted" align="center">
        <x-wirekit::link :href="route('login')" underline="hover">เข้าสู่ระบบผู้ประสานงานมหาวิทยาลัย</x-wirekit::link>
    </x-wirekit::text>
</x-wirekit::stack>
