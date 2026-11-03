<x-layouts.admin-page title="Export Excel">
    <div class="space-y-4">
        <x-wirekit::callout intent="info">ไฟล์ทุกไฟล์จะแสดงเลขประจำตัวแบบปิดบัง ยกเว้นเลือก "รวมเลขบัตรเต็ม" (ใช้ทำประกัน/การเงิน) ทุกการดาวน์โหลดจะถูกบันทึก log</x-wirekit::callout>

        <x-wirekit::card>
            <div class="divide-y divide-slate-100">
                @foreach ($types as $key => $label)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div class="text-sm font-medium text-slate-800">{{ $label }}</div>
                        <div class="flex gap-2">
                            <x-wirekit::button size="sm" intent="neutral" surface="outline" :href="route('admin.exports.download', $key)">ดาวน์โหลด</x-wirekit::button>
                            @if (in_array($key, ['participants', 'receipts']))
                                <x-wirekit::button size="sm" intent="warning" surface="soft" :href="route('admin.exports.download', [$key, 'with_id' => 1])">รวมเลขบัตรเต็ม</x-wirekit::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-wirekit::card>

        <p class="text-sm text-slate-500">รายงานสำหรับกองคลังพร้อมสลิป อยู่ที่เมนู <a href="{{ route('admin.finance') }}" class="text-blue-700 underline">รายงานนำส่งกองคลัง</a></p>
    </div>
</x-layouts.admin-page>
