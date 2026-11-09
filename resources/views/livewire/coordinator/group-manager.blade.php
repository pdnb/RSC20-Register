<x-wirekit::stack gap="lg">
    <x-wirekit::page-header :title="$group->university->name" description="จัดการรายชื่อผู้เข้าร่วม ข้อมูลใบเสร็จ และค่าลงทะเบียนของมหาวิทยาลัย">
        <x-slot:actions>
            <x-wirekit::row gap="sm" wrap>
                <x-wirekit::badge :intent="$group->payment_status->intent()" dot>{{ $group->payment_status->label() }}</x-wirekit::badge>
                @if ($group->payment_method)
                    <x-wirekit::badge intent="info">{{ $group->payment_method->label() }}</x-wirekit::badge>
                @endif
                @if ($group->isEditUnlocked())
                    <x-wirekit::badge intent="warning" leading-icon="edit">ปลดล็อคแก้ไข</x-wirekit::badge>
                @endif
                @if (auth()->user()->isAdmin())
                    @if ($group->isEditUnlocked())
                        <x-wirekit::button size="sm" intent="neutral" surface="outline" wire:click="toggleEditUnlock"
                            wire:confirm="ล็อคการแก้ไขรายชื่อของ {{ $group->university->name }}?">ล็อคการแก้ไข</x-wirekit::button>
                    @else
                        <x-wirekit::button size="sm" intent="warning" surface="soft" wire:click="toggleEditUnlock"
                            wire:confirm="ปลดล็อคให้ผู้ประสานงาน {{ $group->university->name }} แก้ไขข้อมูลผู้เข้าร่วมที่มีอยู่? (เพิ่มหรือลบรายชื่อไม่ได้)">ปลดล็อคให้แก้ไขรายชื่อ</x-wirekit::button>
                    @endif
                @endif
            </x-wirekit::row>
        </x-slot:actions>
    </x-wirekit::page-header>

    @if (! $editable && $editableExisting)
        <x-wirekit::callout intent="info">ผู้ดูแลระบบเปิดให้แก้ไขข้อมูลผู้เข้าร่วมที่มีอยู่ได้ (เพิ่มหรือลบรายชื่อไม่ได้)</x-wirekit::callout>
    @elseif (! $registrationOpen && ! auth()->user()->isAdmin())
        <x-wirekit::callout intent="warning">ขณะนี้อยู่นอกช่วงเวลารับลงทะเบียน จึงแก้ไขรายชื่อไม่ได้</x-wirekit::callout>
    @elseif ($group->isPaid() && ! auth()->user()->isAdmin())
        <x-wirekit::callout intent="info">ชำระเงินเรียบร้อยแล้ว หากต้องการแก้ไขรายชื่อ กรุณาติดต่อผู้ดูแลระบบ</x-wirekit::callout>
    @endif
    @if ($group->reject_reason && ! $group->isPaid())
        <x-wirekit::callout intent="danger">{{ $group->reject_reason }}</x-wirekit::callout>
    @endif

    {{-- Where the group is in the registration flow (a group with nothing to pay — board game only — skips payment) --}}
    @php($nothingToPay = $this->summary['count'] > 0 && $this->summary['total'] <= 0)
    @php($progressSteps = [
        ['label' => 'ข้อมูลใบเสร็จ', 'description' => 'ข้อมูลกลางของมหาวิทยาลัย', 'done' => $group->hasReceiptInfo() || $nothingToPay],
        ['label' => 'เพิ่มรายชื่อ', 'description' => $this->summary['count'].' คน', 'done' => $this->summary['count'] > 0],
        ['label' => 'ชำระเงิน', 'description' => $nothingToPay ? 'ไม่มีค่าลงทะเบียน' : 'แนบสลิปหรือชำระหน้างาน', 'done' => $nothingToPay || ($group->payment_status !== \App\Enums\PaymentStatus::Pending && $group->payment_status !== \App\Enums\PaymentStatus::Rejected)],
        ['label' => 'ยืนยันการชำระ', 'description' => $group->isPaid() || $nothingToPay ? 'เรียบร้อย' : 'รอเจ้าหน้าที่ตรวจสอบ', 'done' => $group->isPaid() || $nothingToPay],
    ])
    @php($currentProgressStep = collect($progressSteps)->search(fn ($s) => ! $s['done']))
    <x-wirekit::card>
        <x-wirekit::card.body>
            <x-wirekit::stepper
                :steps="collect($progressSteps)->map(fn ($s) => ['label' => $s['label'], 'description' => $s['description']])->all()"
                :current="$currentProgressStep === false ? count($progressSteps) + 1 : $currentProgressStep + 1" />
        </x-wirekit::card.body>
    </x-wirekit::card>

    {{-- Fee summary --}}
    <x-wirekit::stats>
        @foreach ($types as $type)
            @php($line = $this->summary['lines']->firstWhere('type', $type->name_th))
            <x-wirekit::stat wire:key="type-{{ $type->id }}" icon="user"
                :label="$type->name_th"
                :value="number_format($line['count'] ?? 0).' คน'"
                :description="number_format($type->fee, 2).' บาท/คน'" />
        @endforeach
        @php($boardGameLimits = \App\Models\Participant::BOARD_GAME_LIMITS)
        <x-wirekit::stat icon="users"
            label="ทีมแข่งขันบอร์ดเกม"
            :value="number_format(array_sum($this->summary['boardGame'])).'/'.array_sum($boardGameLimits).' คน'"
            :description="'บุคลากร '.$this->summary['boardGame'][\App\Models\ParticipantType::STAFF].'/'.$boardGameLimits[\App\Models\ParticipantType::STAFF].' · นักศึกษา '.$this->summary['boardGame'][\App\Models\ParticipantType::STUDENT].'/'.$boardGameLimits[\App\Models\ParticipantType::STUDENT]" />
        <x-wirekit::stat intent="primary" icon="coins"
            label="ยอดรวมที่ต้องชำระ"
            :value="number_format($this->summary['total'], 2).' บาท'"
            :description="'ผู้เข้าร่วมทั้งหมด '.number_format($this->summary['count']).' คน'" />
    </x-wirekit::stats>

    {{-- Shared receipt info (used by everyone unless a participant enters their own) --}}
    <x-wirekit::card>
        <x-wirekit::card.header description="ใช้กับผู้เข้าร่วมทุกคนของมหาวิทยาลัย ยกเว้นผู้ที่ระบุข้อมูลใบเสร็จแยกรายบุคคล">
            <x-wirekit::heading :level="2" size="sm">ข้อมูลสำหรับออกใบเสร็จรับเงิน</x-wirekit::heading>
            <x-slot:actions>
                @if ($group->hasReceiptInfo())
                    <x-wirekit::badge size="sm" intent="success" leading-icon="check">บันทึกแล้ว</x-wirekit::badge>
                @else
                    <x-wirekit::badge size="sm" intent="warning">ยังไม่ได้ระบุ</x-wirekit::badge>
                @endif
            </x-slot:actions>
        </x-wirekit::card.header>
        <x-wirekit::card.body>
            @if ($editable)
                <x-wirekit::form wire:submit="saveReceipt">
                    <x-wirekit::stack gap="md">
                        <x-wirekit::grid cols="1 sm:2" gap="md">
                            <x-wirekit::input wire:model="receipt.name" label="ชื่อผู้รับใบเสร็จ" required />
                            <x-wirekit::input wire:model="receipt.tax_id" label="เลขประจำตัวผู้เสียภาษี (13 หลัก)" inputmode="numeric" maxlength="17" mono required />
                        </x-wirekit::grid>
                        <x-wirekit::textarea wire:model="receipt.address" label="ที่อยู่สำหรับออกใบเสร็จ" rows="2" required />
                        <x-wirekit::row justify="end">
                            <x-wirekit::button type="submit" size="sm" wire:loading.attr="disabled" wire:target="saveReceipt">บันทึกข้อมูลใบเสร็จ</x-wirekit::button>
                        </x-wirekit::row>
                    </x-wirekit::stack>
                </x-wirekit::form>
            @elseif ($group->hasReceiptInfo())
                <x-wirekit::data-list>
                    <x-wirekit::data-list.item label="ชื่อผู้รับใบเสร็จ" :value="$group->receipt_name" />
                    <x-wirekit::data-list.item label="เลขประจำตัวผู้เสียภาษี">
                        <x-wirekit::text as="span" size="sm" family="mono">{{ \App\Support\ThaiId::format($group->receipt_tax_id) }}</x-wirekit::text>
                    </x-wirekit::data-list.item>
                    <x-wirekit::data-list.item label="ที่อยู่" :value="$group->receipt_address" />
                </x-wirekit::data-list>
            @else
                <x-wirekit::text size="sm" intent="muted">ยังไม่ได้ระบุข้อมูลกลาง</x-wirekit::text>
            @endif
        </x-wirekit::card.body>
    </x-wirekit::card>

    @foreach ([
        ['section' => \App\Enums\Section::Executive, 'rows' => $executives],
        ['section' => \App\Enums\Section::Member, 'rows' => $members],
    ] as $block)
        <x-wirekit::card wire:key="section-{{ $block['section']->value }}">
            <x-wirekit::card.header :description="number_format($block['rows']->count()).' คน'">
                <x-wirekit::heading :level="2" size="sm">{{ $block['section']->label() }}</x-wirekit::heading>
                @if ($editable)
                    <x-slot:actions>
                        <x-wirekit::button size="sm" wire:click="create('{{ $block['section']->value }}')">
                            <x-slot:iconLeft><x-wirekit::icon name="user-add" size="sm" /></x-slot:iconLeft>
                            เพิ่มรายชื่อ
                        </x-wirekit::button>
                    </x-slot:actions>
                @endif
            </x-wirekit::card.header>

            @if ($block['rows']->isEmpty())
                <x-wirekit::card.body>
                    <x-wirekit::empty-state icon="users" variant="muted" :level="3"
                        title="ยังไม่มีรายชื่อในส่วนนี้"
                        :description="$editable ? 'กด “เพิ่มรายชื่อ” เพื่อเริ่มลงทะเบียนผู้เข้าร่วม' : null" />
                </x-wirekit::card.body>
            @else
                <x-wirekit::table hoverable stack-below="md">
                    <x-wirekit::table.head>
                        <x-wirekit::table.row>
                            <x-wirekit::table.th>ผู้เข้าร่วม</x-wirekit::table.th>
                            <x-wirekit::table.th>โทรศัพท์</x-wirekit::table.th>
                            <x-wirekit::table.th>เลขบัตร</x-wirekit::table.th>
                            <x-wirekit::table.th>อาหาร / แพ้อาหาร</x-wirekit::table.th>
                            <x-wirekit::table.th>เสื้อ</x-wirekit::table.th>
                            <x-wirekit::table.th align="right">ค่าลงทะเบียน</x-wirekit::table.th>
                            @if ($editableExisting)<x-wirekit::table.th><x-wirekit::visually-hidden>จัดการ</x-wirekit::visually-hidden></x-wirekit::table.th>@endif
                        </x-wirekit::table.row>
                    </x-wirekit::table.head>
                    <x-wirekit::table.body>
                        @foreach ($block['rows'] as $p)
                            <x-wirekit::table.row wire:key="p-{{ $p->id }}">
                                <x-wirekit::table.td>
                                    <x-wirekit::row gap="sm">
                                        <x-wirekit::avatar shape="square" size="md"
                                            :src="$p->badge_photo_path ? route('files.badge', $p).'?v='.$p->updated_at->timestamp : null"
                                            :initials="mb_substr($p->full_name, 0, 1)" from-initials
                                            :alt="$p->displayName()" />
                                        <x-wirekit::stack gap="none">
                                            <x-wirekit::text size="sm" weight="medium">{{ $p->displayName() }}</x-wirekit::text>
                                            <x-wirekit::text size="xs" intent="muted">{{ $p->position }} · {{ $p->code }}</x-wirekit::text>
                                            @if ($p->attendance->includesBoardGame() || (! $p->usesGroupReceipt() && $p->attendsConference()))
                                                <x-wirekit::row gap="xs" wrap>
                                                    @if ($p->attendance->includesBoardGame())
                                                        <x-wirekit::badge size="sm" intent="primary">{{ $p->attendance->shortLabel() }}</x-wirekit::badge>
                                                    @endif
                                                    @if (! $p->usesGroupReceipt() && $p->attendsConference())
                                                        <x-wirekit::badge size="sm" intent="info">ใบเสร็จแยก</x-wirekit::badge>
                                                    @endif
                                                </x-wirekit::row>
                                            @endif
                                        </x-wirekit::stack>
                                    </x-wirekit::row>
                                </x-wirekit::table.td>
                                <x-wirekit::table.td><x-wirekit::text as="span" size="sm" tabular>{{ $p->mobile_phone }}</x-wirekit::text></x-wirekit::table.td>
                                <x-wirekit::table.td><x-wirekit::text as="span" size="xs" family="mono">{{ $p->maskedNationalId() }}</x-wirekit::text></x-wirekit::table.td>
                                <x-wirekit::table.td>
                                    <x-wirekit::text size="sm">{{ $p->food_type ?? '-' }}</x-wirekit::text>
                                    @if ($p->food_allergy)<x-wirekit::text size="xs" intent="danger">แพ้: {{ $p->food_allergy }}</x-wirekit::text>@endif
                                </x-wirekit::table.td>
                                <x-wirekit::table.td><x-wirekit::text as="span" size="sm">{{ $p->shirtSizeLabel() }}</x-wirekit::text></x-wirekit::table.td>
                                <x-wirekit::table.td align="right"><x-wirekit::text as="span" size="sm" tabular>{{ number_format($p->fee_amount, 2) }}</x-wirekit::text></x-wirekit::table.td>
                                @if ($editableExisting)
                                    <x-wirekit::table.td align="right">
                                        <x-wirekit::row gap="xs" justify="end">
                                            <x-wirekit::button size="sm" intent="neutral" surface="ghost" wire:click="edit({{ $p->id }})">
                                                <x-slot:iconLeft><x-wirekit::icon name="edit" size="sm" /></x-slot:iconLeft>
                                                แก้ไข
                                            </x-wirekit::button>
                                            @if ($editable)
                                                <x-wirekit::button size="sm" intent="danger" surface="ghost"
                                                    wire:click="delete({{ $p->id }})"
                                                    wire:confirm="ลบ {{ $p->displayName() }} ออกจากรายชื่อ?">ลบ</x-wirekit::button>
                                            @endif
                                        </x-wirekit::row>
                                    </x-wirekit::table.td>
                                @endif
                            </x-wirekit::table.row>
                        @endforeach
                    </x-wirekit::table.body>
                </x-wirekit::table>
            @endif
        </x-wirekit::card>
    @endforeach

    @if (! auth()->user()->isAdmin() && $this->summary['count'] > 0)
        <x-wirekit::card>
            <x-wirekit::card.body>
                <x-wirekit::row justify="between" gap="md" wrap>
                    <x-wirekit::stack gap="none">
                        <x-wirekit::text size="sm" intent="muted">ยอดที่ต้องชำระ ({{ number_format($this->summary['count']) }} คน)</x-wirekit::text>
                        <x-wirekit::text size="xl" weight="semibold" tabular>{{ number_format($this->summary['total'], 2) }} บาท</x-wirekit::text>
                    </x-wirekit::stack>
                    <x-wirekit::button :href="route('coordinator.payment')" wire:navigate>
                        ไปหน้าชำระเงิน
                        <x-slot:iconRight><x-wirekit::icon name="arrow-right" size="sm" /></x-slot:iconRight>
                    </x-wirekit::button>
                </x-wirekit::row>
            </x-wirekit::card.body>
        </x-wirekit::card>
    @endif

    {{-- Add / edit form (3-step wizard; the step lives on the server so each step validates there) --}}
    @php($wizardSteps = collect(['ข้อมูลผู้เข้าร่วม', 'อาหาร · เสื้อ · รูป', 'ใบเสร็จ · ยืนยัน'])
        ->map(fn ($label, $i) => $i + 1 < $step ? ['label' => $label, 'wire:click' => 'goToStep('.($i + 1).')'] : ['label' => $label])
        ->all())
    @php($boardGameOnly = $form['section'] === 'member' && ($form['attendance'] ?? null) === \App\Enums\Attendance::BoardGame->value)
    <x-wirekit::modal name="participant-form" size="xl" dismissible="false">
        <x-wirekit::modal.header>
            <x-wirekit::row gap="sm" wrap>
                <x-wirekit::text as="span" size="inherit" weight="inherit">{{ $editingId ? 'แก้ไขรายชื่อ' : 'เพิ่มรายชื่อ' }}</x-wirekit::text>
                <x-wirekit::badge size="sm" :intent="$form['section'] === 'executive' ? 'primary' : 'info'">{{ \App\Enums\Section::from($form['section'])->label() }}</x-wirekit::badge>
            </x-wirekit::row>
        </x-wirekit::modal.header>

        <x-wirekit::modal.body>
            <x-wirekit::stack gap="lg">
                <x-wirekit::stepper :steps="$wizardSteps" :current="$step" wire:key="participant-stepper-{{ $step }}" />

                <x-wirekit::form wire:submit="{{ $step < 3 ? 'nextStep' : 'save' }}" id="participant-form">
                    @if ($step === 1)
                        <x-wirekit::stack gap="md" wire:key="participant-step-1">
                            @if ($form['section'] === 'executive')
                                <x-wirekit::input wire:model="form.position" label="ตำแหน่ง" placeholder="เช่น อธิการบดี, รองอธิการบดีฝ่ายกิจการนักศึกษา" required />
                            @else
                                {{-- Attendance first: it decides the fee and which positions still have board game places. --}}
                                @php($seatsLeft = $this->boardGameSeatsLeft)
                                @php($teamFull = array_sum($seatsLeft) === 0)
                                @php($chosenAttendance = \App\Enums\Attendance::tryFrom((string) $form['attendance']) ?? \App\Enums\Attendance::Conference)
                                <x-wirekit::field.set legend="รูปแบบการลงทะเบียน" name="form.attendance" as-field required>
                                    <x-wirekit::grid cols="1 sm:3" gap="sm">
                                        @foreach (\App\Enums\Attendance::cases() as $attendance)
                                            @php($attendanceClosed = $attendance->includesBoardGame() && $teamFull && $chosenAttendance !== $attendance)
                                            <x-wirekit::radio variant="card" wire:model.live="form.attendance" name="form.attendance"
                                                wire:key="attendance-{{ $attendance->value }}"
                                                :value="$attendance->value" :label="$attendance->label()"
                                                :hint="$attendanceClosed ? 'ทีมบอร์ดเกมของมหาวิทยาลัยครบแล้ว' : $attendance->description()"
                                                :disabled="$attendanceClosed" />
                                        @endforeach
                                    </x-wirekit::grid>
                                </x-wirekit::field.set>

                                @if ($chosenAttendance->includesBoardGame())
                                    <x-wirekit::callout intent="info" title="แข่งขันบอร์ดเกม (Board game)">
                                        แข่งขันประกวดการจัดทำสื่อการเรียนรู้ประเภทบอร์ดเกม เพื่อส่งเสริม Student based outcomes วิศวกรสังคม (ระดับประเทศ)
                                        · ทีมละไม่เกิน {{ array_sum(\App\Models\Participant::BOARD_GAME_LIMITS) }} คน
                                        (บุคลากร {{ \App\Models\Participant::BOARD_GAME_LIMITS[\App\Models\ParticipantType::STAFF] }} คน นักศึกษา {{ \App\Models\Participant::BOARD_GAME_LIMITS[\App\Models\ParticipantType::STUDENT] }} คน)
                                    </x-wirekit::callout>
                                @endif

                                <x-wirekit::field.set legend="ตำแหน่ง" name="form.position" as-field required>
                                    <x-wirekit::grid cols="1 sm:2" gap="sm">
                                        @foreach (\App\Models\Participant::MEMBER_POSITIONS as $position => $typeCode)
                                            @php($positionFee = (float) ($types->firstWhere('code', $typeCode)?->fee ?? 0))
                                            @php($positionClosed = $chosenAttendance->includesBoardGame() && $seatsLeft[$typeCode] === 0 && $form['position'] !== $position)
                                            <x-wirekit::radio variant="card" wire:model.live="form.position" name="form.position"
                                                wire:key="position-{{ $typeCode }}"
                                                :value="$position" :label="$position"
                                                :hint="$chosenAttendance->includesConference() ? 'ค่าลงทะเบียน '.number_format($positionFee, 2).' บาท/คน' : 'ไม่มีค่าลงทะเบียน'"
                                                :disabled="$positionClosed">
                                                @if ($chosenAttendance->includesBoardGame())
                                                    <x-slot:trailing>
                                                        @if ($seatsLeft[$typeCode] > 0)
                                                            <x-wirekit::badge size="sm">บอร์ดเกม เหลือ {{ $seatsLeft[$typeCode] }}/{{ \App\Models\Participant::BOARD_GAME_LIMITS[$typeCode] }}</x-wirekit::badge>
                                                        @else
                                                            <x-wirekit::badge size="sm" intent="warning">บอร์ดเกมครบแล้ว</x-wirekit::badge>
                                                        @endif
                                                    </x-slot:trailing>
                                                @endif
                                            </x-wirekit::radio>
                                        @endforeach
                                    </x-wirekit::grid>
                                </x-wirekit::field.set>

                                <x-wirekit::divider label="ข้อมูลส่วนตัว" />
                            @endif

                            <x-wirekit::grid cols="1 sm:3" gap="md">
                                <x-wirekit::select wire:model.live="form.title" label="คำนำหน้า" required
                                    :options="array_combine([...\App\Models\Participant::TITLES, 'อื่นๆ'], [...\App\Models\Participant::TITLES, 'อื่นๆ (ระบุ)'])" />
                                @if ($form['title'] === 'อื่นๆ')
                                    <x-wirekit::input wire:model="form.title_other" label="คำนำหน้า (ระบุ)" required />
                                @endif
                                <x-wirekit::stack gap="none" :class="$form['title'] !== 'อื่นๆ' ? 'sm:col-span-2' : ''">
                                    <x-wirekit::input wire:model="form.full_name" label="ชื่อ-นามสกุล" required />
                                </x-wirekit::stack>
                            </x-wirekit::grid>

                            <x-wirekit::grid cols="1 sm:2" gap="md">
                                <x-wirekit::input wire:model="form.mobile_phone" label="หมายเลขโทรศัพท์มือถือ" type="tel" inputmode="numeric" maxlength="12" placeholder="0812345678" required />
                                <x-wirekit::input wire:model="form.national_id" label="เลขประจำตัวประชาชน (13 หลัก)" inputmode="numeric" maxlength="17" mono required />
                            </x-wirekit::grid>
                        </x-wirekit::stack>
                    @elseif ($step === 2)
                        <x-wirekit::stack gap="lg" wire:key="participant-step-2">
                            @if ($boardGameOnly)
                                <x-wirekit::callout intent="info">ผู้แข่งขันบอร์ดเกมอย่างเดียวไม่มีค่าลงทะเบียนและไม่มีเสื้อ ต้องระบุประเภทอาหารและใช้รูปภาพสำหรับทำป้ายชื่อ</x-wirekit::callout>
                            @endif
                            <x-wirekit::grid cols="1 sm:2" gap="md">
                                <x-wirekit::select wire:model.live="form.food_type" label="ประเภทอาหาร" required
                                    :options="array_combine([...\App\Models\Participant::FOOD_TYPES, 'อื่นๆ'], [...\App\Models\Participant::FOOD_TYPES, 'อื่นๆ (ระบุ)'])" />
                                @if ($form['food_type'] === 'อื่นๆ')
                                    <x-wirekit::input wire:model="form.food_type_other" label="ประเภทอาหาร (ระบุ)" required />
                                @endif
                                <x-wirekit::input wire:model="form.food_allergy" label="อาหารที่แพ้" placeholder="ไม่มี ให้เว้นว่าง" />
                                @unless ($boardGameOnly)
                                    <x-wirekit::select wire:model="form.shirt_size" label="ขนาดไซซ์เสื้อ" required placeholder="เลือกแบบและไซซ์เสื้อ"
                                        :options="\App\Models\Participant::SHIRT_SIZES" />
                                @endunless
                            </x-wirekit::grid>

                            @unless ($boardGameOnly)
                            <x-wirekit::stack gap="sm" align="start" x-data="{ showSizeChart: false }">
                                <x-wirekit::button type="button" size="sm" intent="neutral" surface="ghost" x-on:click="showSizeChart = !showSizeChart">
                                    <x-slot:iconLeft><x-wirekit::icon name="info" size="sm" /></x-slot:iconLeft>
                                    <x-wirekit::text as="span" size="inherit" weight="inherit" intent="inherit" x-text="showSizeChart ? 'ซ่อนตารางไซซ์เสื้อ' : 'ดูตารางไซซ์เสื้อ'">ดูตารางไซซ์เสื้อ</x-wirekit::text>
                                </x-wirekit::button>
                                <x-wirekit::image x-show="showSizeChart" x-cloak :src="asset('images/shirt-size-chart.png')"
                                    rounded class="mx-auto w-full max-w-md" alt="ตารางไซซ์เสื้อโปโลชายและหญิง (รอบอก/ความยาว เป็นนิ้ว)" />
                            </x-wirekit::stack>
                            @endunless

                            <x-wirekit::stack gap="sm">
                                <x-wirekit::label required>รูปภาพสำหรับทำป้ายชื่อ</x-wirekit::label>
                                <x-wirekit::row gap="md" align="start">
                                    {{-- Fixed-size preview box; the image or the placeholder fills it. --}}
                                    <div class="size-28 shrink-0 overflow-hidden rounded-xl">
                                        @if ($photo && method_exists($photo, 'temporaryUrl') && in_array(strtolower($photo->getClientOriginalExtension()), ['jpg', 'jpeg', 'png']))
                                            <x-wirekit::image :src="$photo->temporaryUrl()" alt="" ratio="1/1" loading="eager" />
                                        @elseif ($form['badge_photo_path'] && $editingId)
                                            <x-wirekit::image :src="route('files.badge', $editingId).'?v='.now()->timestamp" alt="" ratio="1/1" loading="eager" />
                                        @else
                                            <x-wirekit::center class="h-full bg-(--color-wk-bg-muted)">
                                                <x-wirekit::stack gap="xs" align="center">
                                                    <x-wirekit::text as="span" intent="subtle"><x-wirekit::icon name="user" size="lg" /></x-wirekit::text>
                                                    <x-wirekit::text as="span" size="xs" intent="subtle">ยังไม่มีรูป</x-wirekit::text>
                                                </x-wirekit::stack>
                                            </x-wirekit::center>
                                        @endif
                                    </div>
                                    <x-wirekit::stack gap="xs" class="min-w-0 flex-1">
                                        <x-wirekit::file-upload wire:model="photo" accept="image/jpeg,image/png"
                                            hint="jpg/png ไม่เกิน 5MB ระบบจะครอปเป็นสี่เหลี่ยมจัตุรัสให้อัตโนมัติ"
                                            :error="$errors->first('photo')" />
                                        <x-wirekit::text size="xs" intent="muted" wire:loading wire:target="photo">กำลังอัปโหลด…</x-wirekit::text>
                                    </x-wirekit::stack>
                                </x-wirekit::row>
                            </x-wirekit::stack>
                        </x-wirekit::stack>
                    @else
                        <x-wirekit::stack gap="lg" wire:key="participant-step-3">
                            @if ($boardGameOnly)
                                <x-wirekit::callout intent="info">ไม่มีค่าลงทะเบียน จึงไม่ต้องระบุข้อมูลใบเสร็จ</x-wirekit::callout>
                            @else
                            <x-wirekit::stack gap="md">
                                <x-wirekit::checkbox wire:model.live="form.use_group_receipt" label="ใช้ข้อมูลใบเสร็จกลางของมหาวิทยาลัย" />
                                @if ($form['use_group_receipt'])
                                    @if ($group->hasReceiptInfo())
                                        <x-wirekit::card surface="flat" class="bg-(--color-wk-bg-subtle)">
                                            <x-wirekit::card.body>
                                                <x-wirekit::stack gap="none">
                                                    <x-wirekit::text size="sm" weight="medium">{{ $group->receipt_name }}</x-wirekit::text>
                                                    <x-wirekit::text size="xs" family="mono">{{ \App\Support\ThaiId::format($group->receipt_tax_id) }}</x-wirekit::text>
                                                    <x-wirekit::text size="sm" intent="muted">{{ $group->receipt_address }}</x-wirekit::text>
                                                </x-wirekit::stack>
                                            </x-wirekit::card.body>
                                        </x-wirekit::card>
                                    @else
                                        <x-wirekit::callout intent="warning">ยังไม่ได้บันทึกข้อมูลใบเสร็จกลาง กรุณาบันทึกที่ส่วน "ข้อมูลสำหรับออกใบเสร็จรับเงิน (ข้อมูลกลาง)" หรือยกเลิกการเลือกเพื่อระบุแยกรายบุคคล</x-wirekit::callout>
                                    @endif
                                    @error('form.use_group_receipt')<x-wirekit::text size="xs" intent="danger" role="alert">{{ $message }}</x-wirekit::text>@enderror
                                @else
                                    <x-wirekit::row gap="sm" justify="between" wrap>
                                        <x-wirekit::text size="sm" variant="muted">ออกใบเสร็จในนามบุคคล</x-wirekit::text>
                                        <x-wirekit::button type="button" size="sm" intent="neutral" surface="soft" wire:click="fillReceiptFromParticipant">
                                            <x-slot:iconLeft><x-wirekit::icon name="copy" size="sm" /></x-slot:iconLeft>
                                            ใช้ชื่อและเลขบัตรของผู้เข้าร่วม
                                        </x-wirekit::button>
                                    </x-wirekit::row>
                                    <x-wirekit::grid cols="1 sm:2" gap="md">
                                        <x-wirekit::input wire:model="form.receipt_name" label="ชื่อผู้รับใบเสร็จ" required />
                                        <x-wirekit::input wire:model="form.receipt_tax_id" label="เลขประจำตัวผู้เสียภาษี (13 หลัก)" inputmode="numeric" maxlength="17" mono required />
                                    </x-wirekit::grid>
                                    <x-wirekit::textarea wire:model="form.receipt_address" label="ที่อยู่สำหรับออกใบเสร็จ" rows="2" required />
                                @endif
                            </x-wirekit::stack>
                            @endif

                            <x-wirekit::card>
                                <x-wirekit::card.body>
                                <x-wirekit::data-list>
                                    <x-wirekit::data-list.item label="ผู้เข้าร่วม">
                                        {{ $form['title'] === 'อื่นๆ' ? $form['title_other'] : $form['title'] }}{{ $form['full_name'] }}
                                    </x-wirekit::data-list.item>
                                    <x-wirekit::data-list.item label="ตำแหน่ง" :value="$form['position']" />
                                    @if ($form['section'] === 'member')
                                        <x-wirekit::data-list.item label="รูปแบบการลงทะเบียน" :value="\App\Enums\Attendance::tryFrom($form['attendance'])?->label() ?? '-'" />
                                    @endif
                                    <x-wirekit::data-list.item label="โทรศัพท์" :value="$form['mobile_phone']" />
                                    <x-wirekit::data-list.item label="อาหาร">
                                        {{ $form['food_type'] === 'อื่นๆ' ? $form['food_type_other'] : $form['food_type'] }}
                                        @if ($form['food_allergy'])<x-wirekit::text as="span" size="inherit" intent="danger">· แพ้ {{ $form['food_allergy'] }}</x-wirekit::text>@endif
                                    </x-wirekit::data-list.item>
                                    @if ($boardGameOnly)
                                        <x-wirekit::data-list.item label="เสื้อ" value="ไม่มี (แข่งขันบอร์ดเกมอย่างเดียว)" />
                                    @else
                                        <x-wirekit::data-list.item label="เสื้อ" :value="\App\Models\Participant::SHIRT_SIZES[$form['shirt_size']] ?? '-'" />
                                    @endif
                                </x-wirekit::data-list>
                                </x-wirekit::card.body>
                            </x-wirekit::card>

                            @unless ($group->pdpa_accepted_at)
                                <x-wirekit::callout intent="neutral" :icon="false">
                                    <x-wirekit::stack gap="sm">
                                        <x-wirekit::text size="inherit" intent="inherit">{{ $pdpaText }}</x-wirekit::text>
                                        <x-wirekit::checkbox wire:model="pdpa" label="ยินยอมและยืนยัน" />
                                    </x-wirekit::stack>
                                </x-wirekit::callout>
                            @endunless
                            @error('pdpa')<x-wirekit::text size="xs" intent="danger" role="alert">{{ $message }}</x-wirekit::text>@enderror
                        </x-wirekit::stack>
                    @endif
                </x-wirekit::form>
            </x-wirekit::stack>
        </x-wirekit::modal.body>

        <x-wirekit::modal.footer>
            <x-wirekit::text size="sm" intent="muted" class="mr-auto">ค่าลงทะเบียน: <x-wirekit::text as="span" size="inherit" weight="semibold" intent="accent">{{ number_format($this->formFee, 2) }} บาท</x-wirekit::text></x-wirekit::text>
            @if ($step === 1)
                <x-wirekit::modal.close>
                    <x-wirekit::button intent="neutral" surface="ghost">ยกเลิก</x-wirekit::button>
                </x-wirekit::modal.close>
            @else
                <x-wirekit::button intent="neutral" surface="ghost" wire:click="previousStep">← ย้อนกลับ</x-wirekit::button>
            @endif
            @if ($step < 3)
                <x-wirekit::button type="submit" form="participant-form" wire:loading.attr="disabled" wire:target="nextStep,photo">ถัดไป →</x-wirekit::button>
            @else
                @unless ($editingId)
                    <x-wirekit::button intent="neutral" surface="outline" wire:click="save(true)" wire:loading.attr="disabled" wire:target="save,photo">บันทึกและเพิ่มคนถัดไป</x-wirekit::button>
                @endunless
                <x-wirekit::button type="submit" form="participant-form" wire:loading.attr="disabled" wire:target="save,photo">บันทึก</x-wirekit::button>
            @endif
        </x-wirekit::modal.footer>
    </x-wirekit::modal>
</x-wirekit::stack>
