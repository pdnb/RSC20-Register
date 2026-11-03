<x-layouts.admin-page title="ดาวน์โหลดข้อมูล">
    <x-wirekit::stack gap="md">
        <x-wirekit::callout intent="info">เลขบัตรประชาชนในไฟล์จะแสดงแบบซ่อนบางส่วน หากต้องการเลขเต็มสำหรับทำประกันหรืองานการเงิน ให้กด "รวมเลขบัตรเต็ม" ระบบจะเก็บประวัติการดาวน์โหลดทุกครั้ง</x-wirekit::callout>

        <x-wirekit::card>
            <x-wirekit::stack gap="none" class="divide-y divide-(--color-wk-border-subtle)">
                @foreach ($types as $key => $label)
                    <x-wirekit::row gap="sm" align="center" justify="between" wrap class="px-4 py-3">
                        <x-wirekit::text size="sm" weight="medium">{{ $label }}</x-wirekit::text>
                        <x-wirekit::row gap="sm">
                            <x-wirekit::button size="sm" intent="neutral" surface="outline" :href="route('admin.exports.download', $key)">ดาวน์โหลด</x-wirekit::button>
                            @if (in_array($key, ['participants', 'receipts']))
                                <x-wirekit::button size="sm" intent="warning" surface="soft" :href="route('admin.exports.download', [$key, 'with_id' => 1])">รวมเลขบัตรเต็ม</x-wirekit::button>
                            @endif
                        </x-wirekit::row>
                    </x-wirekit::row>
                @endforeach
            </x-wirekit::stack>
        </x-wirekit::card>

        <x-wirekit::text size="sm" intent="muted">รายงานสำหรับกองคลังพร้อมสลิป อยู่ที่เมนู <x-wirekit::link :href="route('admin.finance')">รายงานนำส่งกองคลัง</x-wirekit::link></x-wirekit::text>
    </x-wirekit::stack>
</x-layouts.admin-page>
