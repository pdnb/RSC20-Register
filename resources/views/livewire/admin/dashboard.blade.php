<x-wirekit::stack gap="lg">
    @if ($feesMissing)
        <x-wirekit::callout intent="warning">ยังไม่ได้กำหนดค่าลงทะเบียนบางประเภท <x-wirekit::link :href="route('admin.settings')">ไปหน้าตั้งค่า</x-wirekit::link></x-wirekit::callout>
    @endif

    <x-wirekit::page-header title="ภาพรวมการลงทะเบียน" description="สรุปผู้ลงทะเบียน การชำระเงิน และการเช็กอินของงาน RSC20">
        <x-slot:actions>
            <x-wirekit::row gap="sm" wrap>
                <x-wirekit::button :href="route('admin.groups')" wire:navigate intent="neutral" surface="outline" size="sm">
                    <x-slot:iconLeft><x-wirekit::icon name="receipt" size="sm" /></x-slot:iconLeft>
                    ตรวจสลิป
                    @if ($stats['pendingSlips'] > 0)
                        <x-wirekit::badge size="sm" intent="warning">{{ number_format($stats['pendingSlips']) }}</x-wirekit::badge>
                    @endif
                </x-wirekit::button>
                <x-wirekit::button :href="route('admin.exports')" intent="neutral" surface="outline" size="sm">
                    <x-slot:iconLeft><x-wirekit::icon name="download" size="sm" /></x-slot:iconLeft>
                    ดาวน์โหลดข้อมูล
                </x-wirekit::button>
                <x-wirekit::button :href="route('staff.checkin')" wire:navigate intent="primary" size="sm">
                    <x-slot:iconLeft><x-wirekit::icon name="scan" size="sm" /></x-slot:iconLeft>
                    เช็กอินหน้างาน
                </x-wirekit::button>
            </x-wirekit::row>
        </x-slot:actions>
    </x-wirekit::page-header>

    <x-wirekit::stats cols="4">
        <x-wirekit::stat label="ผู้ลงทะเบียน" icon="users"
            :value="number_format($stats['participants']).' คน'"
            :description="'จาก '.number_format($stats['universities']).' มหาวิทยาลัย'" />

        <x-wirekit::stat label="ยอดชำระแล้ว" icon="coins"
            :value="number_format($stats['paid'], 2).' ฿'"
            :description="'จากยอดที่ต้องได้รับ '.number_format($stats['expected'], 2).' ฿'">
            <x-slot:progress>
                <x-wirekit::progress :value="$stats['paidPercent']" intent="success" size="sm" label="{{ $stats['paidPercent'] }}% ของยอดทั้งหมด" />
            </x-slot:progress>
        </x-wirekit::stat>

        <x-wirekit::stat label="สลิปรอตรวจ" icon="list-checks"
            :intent="$stats['pendingSlips'] > 0 ? 'warning' : null"
            :href="route('admin.groups')"
            :value="number_format($stats['pendingSlips']).' กลุ่ม'"
            :description="$stats['pendingSlips'] > 0 ? 'คลิกเพื่อไปตรวจสลิป' : 'ไม่มีสลิปค้างตรวจ'" />

        <x-wirekit::stat label="เช็กอินแล้ว" icon="success"
            :value="number_format($stats['checkedIn']).' คน'"
            :description="'จาก '.number_format($stats['participants']).' คน'">
            <x-slot:progress>
                <x-wirekit::progress :value="$stats['checkedInPercent']" size="sm" label="{{ $stats['checkedInPercent'] }}% ของผู้ลงทะเบียน" />
            </x-slot:progress>
        </x-wirekit::stat>
    </x-wirekit::stats>

    <x-wirekit::grid cols="1 lg:2" gap="md">
        <x-wirekit::card>
            <x-wirekit::card.header description="จำนวนผู้ลงทะเบียนแยกตามประเภทและค่าลงทะเบียน">
                <x-wirekit::heading :level="2" size="sm">ตามประเภทผู้เข้าร่วม</x-wirekit::heading>
            </x-wirekit::card.header>
            <x-wirekit::card.body>
                <x-wirekit::stack gap="md">
                    @forelse ($byType as $type)
                        <x-wirekit::stack gap="xs" wire:key="type-{{ $type->id }}">
                            <x-wirekit::row justify="between" gap="sm">
                                <x-wirekit::text size="sm">{{ $type->name_th }} <x-wirekit::text as="span" size="xs" intent="muted">· {{ number_format($type->fee, 0) }} ฿</x-wirekit::text></x-wirekit::text>
                                <x-wirekit::text size="sm" weight="semibold" tabular>{{ number_format($type->participants_count) }}</x-wirekit::text>
                            </x-wirekit::row>
                            <x-wirekit::progress :value="$type->participants_count" :max="max($stats['participants'], 1)" size="sm" />
                        </x-wirekit::stack>
                    @empty
                        <x-wirekit::text size="sm" intent="muted">ยังไม่มีประเภทผู้เข้าร่วม</x-wirekit::text>
                    @endforelse
                </x-wirekit::stack>
            </x-wirekit::card.body>
        </x-wirekit::card>

        <x-wirekit::card>
            <x-wirekit::card.header description="ใช้สำหรับสั่งผลิตเสื้อที่ระลึก">
                <x-wirekit::heading :level="2" size="sm">ไซซ์เสื้อ</x-wirekit::heading>
            </x-wirekit::card.header>
            <x-wirekit::card.body>
                @php($shirtMax = max(1, (int) $byShirt->max()))
                <x-wirekit::stack gap="sm">
                    @foreach (\App\Models\Participant::SHIRT_SIZES as $size => $label)
                        <x-wirekit::grid template="7rem 1fr 2.5rem" gap="sm" align="center" wire:key="shirt-{{ $size }}">
                            <x-wirekit::text size="xs" intent="muted" truncate="tooltip">{{ $label }}</x-wirekit::text>
                            <x-wirekit::progress :value="$byShirt[$size] ?? 0" :max="$shirtMax" intent="info" size="sm" />
                            <x-wirekit::text size="sm" weight="semibold" align="right" tabular>{{ number_format($byShirt[$size] ?? 0) }}</x-wirekit::text>
                        </x-wirekit::grid>
                    @endforeach
                </x-wirekit::stack>
            </x-wirekit::card.body>
        </x-wirekit::card>
    </x-wirekit::grid>

    <x-wirekit::grid cols="1 md:2 xl:3" gap="md">
        <x-wirekit::card>
            <x-wirekit::card.header :description="'ทั้งหมด '.number_format($stats['groups']).' กลุ่ม'">
                <x-wirekit::heading :level="2" size="sm">สถานะการชำระเงิน</x-wirekit::heading>
            </x-wirekit::card.header>
            <x-wirekit::card.body>
                <x-wirekit::stack gap="md">
                    @foreach ($byStatus as $value => $row)
                        <x-wirekit::stack gap="xs" wire:key="status-{{ $value }}">
                            <x-wirekit::row justify="between" gap="sm">
                                <x-wirekit::badge size="sm" :intent="$row['status']->intent()">{{ $row['status']->label() }}</x-wirekit::badge>
                                <x-wirekit::text size="sm" weight="semibold" tabular>{{ number_format($row['count']) }}</x-wirekit::text>
                            </x-wirekit::row>
                            <x-wirekit::progress :value="$row['count']" :max="max($stats['groups'], 1)" :intent="$row['status']->intent()" size="sm" />
                        </x-wirekit::stack>
                    @endforeach
                </x-wirekit::stack>
            </x-wirekit::card.body>
        </x-wirekit::card>

        <x-wirekit::card>
            <x-wirekit::card.header description="ประเภทอาหารที่ผู้เข้าร่วมเลือก">
                <x-wirekit::heading :level="2" size="sm">อาหาร</x-wirekit::heading>
            </x-wirekit::card.header>
            <x-wirekit::card.body>
                <x-wirekit::stack gap="md">
                    @if ($byFood->isEmpty())
                        <x-wirekit::text size="sm" intent="muted">ยังไม่มีข้อมูล</x-wirekit::text>
                    @else
                        <x-wirekit::data-list>
                            @foreach ($byFood as $food => $count)
                                <x-wirekit::data-list.item :label="$food ?: '-'" :value="number_format($count)" />
                            @endforeach
                        </x-wirekit::data-list>
                    @endif
                    <x-wirekit::row justify="between" gap="sm">
                        <x-wirekit::text size="sm" intent="danger">มีอาหารที่แพ้</x-wirekit::text>
                        <x-wirekit::badge size="sm" :intent="$allergies > 0 ? 'danger' : 'neutral'">{{ number_format($allergies) }} คน</x-wirekit::badge>
                    </x-wirekit::row>
                </x-wirekit::stack>
            </x-wirekit::card.body>
        </x-wirekit::card>

        <x-wirekit::card>
            <x-wirekit::card.header description="มหาวิทยาลัยที่ลงทะเบียนเข้าร่วมแล้ว">
                <x-wirekit::heading :level="2" size="sm">ความครอบคลุม</x-wirekit::heading>
            </x-wirekit::card.header>
            <x-wirekit::card.body>
                <x-wirekit::stack gap="sm" align="center">
                    <x-wirekit::radial-progress size="lg"
                        :value="$stats['registeredUniversities']"
                        :max="max($stats['totalUniversities'], 1)"
                        label="มหาวิทยาลัยที่ลงทะเบียนแล้ว"
                        :value-text="$stats['registeredUniversities'].' จาก '.$stats['totalUniversities'].' มหาวิทยาลัย'">
                        {{ $stats['registeredUniversities'] }}/{{ $stats['totalUniversities'] }}
                    </x-wirekit::radial-progress>
                    <x-wirekit::text size="sm" intent="muted" align="center">ลงทะเบียนแล้ว {{ $stats['registeredUniversities'] }} จาก {{ $stats['totalUniversities'] }} มหาวิทยาลัย</x-wirekit::text>
                </x-wirekit::stack>
            </x-wirekit::card.body>
        </x-wirekit::card>
    </x-wirekit::grid>

    <x-wirekit::card>
        <x-wirekit::card.header :description="'ลงทะเบียนแล้ว '.$stats['registeredUniversities'].' / '.$stats['totalUniversities'].' มหาวิทยาลัย'">
            <x-wirekit::heading :level="2" size="sm">รายมหาวิทยาลัย</x-wirekit::heading>
        </x-wirekit::card.header>
        <x-wirekit::table hoverable compact stack-below="md">
            <x-wirekit::table.head>
                <x-wirekit::table.row>
                    <x-wirekit::table.th>มหาวิทยาลัย</x-wirekit::table.th>
                    <x-wirekit::table.th align="right">ผู้เข้าร่วม</x-wirekit::table.th>
                    <x-wirekit::table.th align="right">ยอดเงิน (฿)</x-wirekit::table.th>
                    <x-wirekit::table.th>สถานะ</x-wirekit::table.th>
                    <x-wirekit::table.th>เช็กอิน</x-wirekit::table.th>
                </x-wirekit::table.row>
            </x-wirekit::table.head>
            <x-wirekit::table.body>
                @foreach ($byUniversity as $u)
                    @php($groupParticipants = $u->group?->participants_count ?? 0)
                    @php($groupCheckedIn = $u->group?->checked_in_count ?? 0)
                    <x-wirekit::table.row wire:key="uni-{{ $u->id }}">
                        <x-wirekit::table.td>
                            @if ($u->group)
                                <x-wirekit::link :href="route('admin.groups.show', $u->group)" underline="hover">{{ $u->name }}</x-wirekit::link>
                            @else
                                <x-wirekit::text as="span" size="sm" intent="subtle">{{ $u->name }}</x-wirekit::text>
                            @endif
                        </x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ number_format($groupParticipants) }}</x-wirekit::table.td>
                        <x-wirekit::table.td align="right">{{ number_format($u->group?->total_amount ?? 0, 2) }}</x-wirekit::table.td>
                        <x-wirekit::table.td>
                            @if ($u->group)
                                <x-wirekit::badge size="sm" :intent="$u->group->payment_status->intent()">{{ $u->group->payment_status->label() }}</x-wirekit::badge>
                            @else
                                <x-wirekit::text as="span" size="xs" intent="subtle">ยังไม่ลงทะเบียน</x-wirekit::text>
                            @endif
                        </x-wirekit::table.td>
                        <x-wirekit::table.td>
                            @if ($groupParticipants > 0)
                                <x-wirekit::row gap="sm">
                                    <x-wirekit::progress class="min-w-16 flex-1" :value="$groupCheckedIn" :max="$groupParticipants" :intent="$groupCheckedIn === $groupParticipants ? 'success' : 'primary'" size="sm" />
                                    <x-wirekit::text as="span" size="xs" intent="muted" tabular>{{ $groupCheckedIn }}/{{ $groupParticipants }}</x-wirekit::text>
                                </x-wirekit::row>
                            @else
                                <x-wirekit::text as="span" size="xs" intent="subtle">-</x-wirekit::text>
                            @endif
                        </x-wirekit::table.td>
                    </x-wirekit::table.row>
                @endforeach
            </x-wirekit::table.body>
        </x-wirekit::table>
    </x-wirekit::card>
</x-wirekit::stack>
