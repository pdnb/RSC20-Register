<x-wirekit::stack gap="lg">
    <x-wirekit::page-header :title="$group->university->name" description="จัดการรายชื่อผู้เข้าร่วม ข้อมูลใบเสร็จ และค่าลงทะเบียนของมหาวิทยาลัย">
        <x-slot:actions>
            <x-wirekit::row gap="sm" wrap>
                <x-wirekit::badge :intent="$group->payment_status->intent()" dot>{{ $group->payment_status->label() }}</x-wirekit::badge>
                @if ($group->payment_method)
                    <x-wirekit::badge intent="info">{{ $group->payment_method->label() }}</x-wirekit::badge>
                @endif
            </x-wirekit::row>
        </x-slot:actions>
    </x-wirekit::page-header>

    @if (! $registrationOpen && ! auth()->user()->isAdmin())
        <x-wirekit::callout intent="warning">ขณะนี้อยู่นอกช่วงเวลารับลงทะเบียน จึงแก้ไขรายชื่อไม่ได้</x-wirekit::callout>
    @elseif ($group->isPaid() && ! auth()->user()->isAdmin())
        <x-wirekit::callout intent="info">ชำระเงินเรียบร้อยแล้ว หากต้องการแก้ไขรายชื่อ กรุณาติดต่อผู้ดูแลระบบ</x-wirekit::callout>
    @endif
    @if ($group->reject_reason && ! $group->isPaid())
        <x-wirekit::callout intent="danger">{{ $group->reject_reason }}</x-wirekit::callout>
    @endif

    {{-- Where the group is in the registration flow --}}
    @php($progressSteps = [
        ['label' => 'ข้อมูลใบเสร็จ', 'description' => 'ข้อมูลกลางของมหาวิทยาลัย', 'done' => $group->hasReceiptInfo()],
        ['label' => 'เพิ่มรายชื่อ', 'description' => $this->summary['count'].' คน', 'done' => $this->summary['count'] > 0],
        ['label' => 'ชำระเงิน', 'description' => 'แนบสลิปหรือชำระหน้างาน', 'done' => $group->payment_status !== \App\Enums\PaymentStatus::Pending && $group->payment_status !== \App\Enums\PaymentStatus::Rejected],
        ['label' => 'ยืนยันการชำระ', 'description' => $group->isPaid() ? 'เรียบร้อย' : 'รอเจ้าหน้าที่ตรวจสอบ', 'done' => $group->isPaid()],
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
                <form wire:submit="saveReceipt">
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
                </form>
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
                            @if ($editable)<x-wirekit::table.th><x-wirekit::visually-hidden>จัดการ</x-wirekit::visually-hidden></x-wirekit::table.th>@endif
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
                                            @unless ($p->usesGroupReceipt())
                                                <x-wirekit::row gap="none"><x-wirekit::badge size="sm" intent="info">ใบเสร็จแยก</x-wirekit::badge></x-wirekit::row>
                                            @endunless
                                        </x-wirekit::stack>
                                    </x-wirekit::row>
                                </x-wirekit::table.td>
                                <x-wirekit::table.td><x-wirekit::text as="span" size="sm" tabular>{{ $p->mobile_phone }}</x-wirekit::text></x-wirekit::table.td>
                                <x-wirekit::table.td><x-wirekit::text as="span" size="xs" family="mono">{{ $p->maskedNationalId() }}</x-wirekit::text></x-wirekit::table.td>
                                <x-wirekit::table.td>
                                    <x-wirekit::text size="sm">{{ $p->food_type }}</x-wirekit::text>
                                    @if ($p->food_allergy)<x-wirekit::text size="xs" intent="danger">แพ้: {{ $p->food_allergy }}</x-wirekit::text>@endif
                                </x-wirekit::table.td>
                                <x-wirekit::table.td><x-wirekit::text as="span" size="sm">{{ $p->shirtSizeLabel() }}</x-wirekit::text></x-wirekit::table.td>
                                <x-wirekit::table.td align="right"><x-wirekit::text as="span" size="sm" tabular>{{ number_format($p->fee_amount, 2) }}</x-wirekit::text></x-wirekit::table.td>
                                @if ($editable)
                                    <x-wirekit::table.td align="right">
                                        <x-wirekit::row gap="xs" justify="end">
                                            <x-wirekit::button size="sm" intent="neutral" surface="ghost" wire:click="edit({{ $p->id }})">
                                                <x-slot:iconLeft><x-wirekit::icon name="edit" size="sm" /></x-slot:iconLeft>
                                                แก้ไข
                                            </x-wirekit::button>
                                            <x-wirekit::button size="sm" intent="danger" surface="ghost"
                                                wire:click="delete({{ $p->id }})"
                                                wire:confirm="ลบ {{ $p->displayName() }} ออกจากรายชื่อ?">ลบ</x-wirekit::button>
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
    <x-wirekit::modal name="participant-form" size="xl" dismissible="false">
        <x-wirekit::modal.header>
            <x-wirekit::row gap="sm" wrap>
                <span>{{ $editingId ? 'แก้ไขรายชื่อ' : 'เพิ่มรายชื่อ' }}</span>
                <x-wirekit::badge size="sm" :intent="$form['section'] === 'executive' ? 'primary' : 'info'">{{ \App\Enums\Section::from($form['section'])->label() }}</x-wirekit::badge>
            </x-wirekit::row>
        </x-wirekit::modal.header>

        <x-wirekit::modal.body>
            <x-wirekit::stack gap="lg">
                <x-wirekit::stepper :steps="$wizardSteps" :current="$step" wire:key="participant-stepper-{{ $step }}" />

                <form wire:submit="{{ $step < 3 ? 'nextStep' : 'save' }}" id="participant-form">
                    @if ($step === 1)
                        <x-wirekit::stack gap="md" wire:key="participant-step-1">
                            @if ($form['section'] === 'executive')
                                <x-wirekit::input wire:model="form.position" label="ตำแหน่ง" placeholder="เช่น อธิการบดี, รองอธิการบดีฝ่ายกิจการนักศึกษา" required />
                            @else
                                <x-wirekit::field.set legend="ตำแหน่ง" name="form.position" as-field required>
                                    <x-wirekit::grid cols="2" gap="sm">
                                        @foreach (['บุคลากร', 'นักศึกษา'] as $position)
                                            <x-wirekit::radio variant="card" wire:model.live="form.position" name="form.position" :value="$position" :label="$position" />
                                        @endforeach
                                    </x-wirekit::grid>
                                </x-wirekit::field.set>
                            @endif

                            <x-wirekit::grid cols="1 sm:3" gap="md">
                                <x-wirekit::select wire:model.live="form.title" label="คำนำหน้า" required
                                    :options="array_combine([...\App\Models\Participant::TITLES, 'อื่นๆ'], [...\App\Models\Participant::TITLES, 'อื่นๆ (ระบุ)'])" />
                                @if ($form['title'] === 'อื่นๆ')
                                    <x-wirekit::input wire:model="form.title_other" label="คำนำหน้า (ระบุ)" required />
                                @endif
                                <div @class(['sm:col-span-2' => $form['title'] !== 'อื่นๆ'])>
                                    <x-wirekit::input wire:model="form.full_name" label="ชื่อ-นามสกุล" required />
                                </div>
                            </x-wirekit::grid>

                            <x-wirekit::grid cols="1 sm:2" gap="md">
                                <x-wirekit::input wire:model="form.mobile_phone" label="หมายเลขโทรศัพท์มือถือ" type="tel" inputmode="numeric" maxlength="12" placeholder="0812345678" required />
                                <x-wirekit::input wire:model="form.national_id" label="เลขประจำตัวประชาชน (13 หลัก)" inputmode="numeric" maxlength="17" mono required />
                            </x-wirekit::grid>
                        </x-wirekit::stack>
                    @elseif ($step === 2)
                        <x-wirekit::stack gap="lg" wire:key="participant-step-2">
                            <x-wirekit::grid cols="1 sm:2" gap="md">
                                <x-wirekit::select wire:model.live="form.food_type" label="ประเภทอาหาร" required
                                    :options="array_combine([...\App\Models\Participant::FOOD_TYPES, 'อื่นๆ'], [...\App\Models\Participant::FOOD_TYPES, 'อื่นๆ (ระบุ)'])" />
                                @if ($form['food_type'] === 'อื่นๆ')
                                    <x-wirekit::input wire:model="form.food_type_other" label="ประเภทอาหาร (ระบุ)" required />
                                @endif
                                <x-wirekit::input wire:model="form.food_allergy" label="อาหารที่แพ้" placeholder="ไม่มี ให้เว้นว่าง" />
                                <x-wirekit::select wire:model="form.shirt_size" label="ขนาดไซซ์เสื้อ" required placeholder="เลือกแบบและไซซ์เสื้อ"
                                    :options="\App\Models\Participant::SHIRT_SIZES" />
                            </x-wirekit::grid>

                            <div x-data="{ showSizeChart: false }">
                                <x-wirekit::button type="button" size="sm" intent="neutral" surface="ghost" x-on:click="showSizeChart = !showSizeChart">
                                    <x-slot:iconLeft><x-wirekit::icon name="info" size="sm" /></x-slot:iconLeft>
                                    <span x-text="showSizeChart ? 'ซ่อนตารางไซซ์เสื้อ' : 'ดูตารางไซซ์เสื้อ'">ดูตารางไซซ์เสื้อ</span>
                                </x-wirekit::button>
                                <img x-show="showSizeChart" x-cloak src="{{ asset('images/shirt-size-chart.png') }}"
                                    class="mx-auto mt-2 w-full max-w-md rounded-xl" alt="ตารางไซซ์เสื้อโปโลชายและหญิง (รอบอก/ความยาว เป็นนิ้ว)" loading="lazy">
                            </div>

                            <x-wirekit::stack gap="sm">
                                <x-wirekit::text size="sm" weight="medium">รูปภาพสำหรับทำป้ายชื่อ<span class="ms-0.5 text-(--color-wk-danger-text)" aria-hidden="true">*</span></x-wirekit::text>
                                <x-wirekit::row gap="md" align="start">
                                    @if ($photo && method_exists($photo, 'temporaryUrl') && in_array(strtolower($photo->getClientOriginalExtension()), ['jpg', 'jpeg', 'png']))
                                        <img src="{{ $photo->temporaryUrl() }}" class="size-28 shrink-0 rounded-xl object-cover" alt="">
                                    @elseif ($form['badge_photo_path'] && $editingId)
                                        <img src="{{ route('files.badge', $editingId) }}?v={{ now()->timestamp }}" class="size-28 shrink-0 rounded-xl object-cover" alt="">
                                    @else
                                        <div class="flex size-28 shrink-0 flex-col items-center justify-center gap-1 rounded-xl bg-slate-100 text-xs text-slate-400">
                                            <x-wirekit::icon name="user" size="lg" />
                                            ยังไม่มีรูป
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <x-wirekit::file-upload wire:model="photo" accept="image/jpeg,image/png"
                                            hint="jpg/png ไม่เกิน 5MB ระบบจะครอปเป็นสี่เหลี่ยมจัตุรัสให้อัตโนมัติ"
                                            :error="$errors->first('photo')" />
                                        <div wire:loading wire:target="photo" class="mt-1 text-xs text-slate-500">กำลังอัปโหลด…</div>
                                    </div>
                                </x-wirekit::row>
                            </x-wirekit::stack>
                        </x-wirekit::stack>
                    @else
                        <x-wirekit::stack gap="lg" wire:key="participant-step-3">
                            <x-wirekit::stack gap="md">
                                <x-wirekit::checkbox wire:model.live="form.use_group_receipt" label="ใช้ข้อมูลใบเสร็จกลางของมหาวิทยาลัย" />
                                @if ($form['use_group_receipt'])
                                    @if ($group->hasReceiptInfo())
                                        <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                            <div class="font-medium text-slate-900">{{ $group->receipt_name }}</div>
                                            <div class="font-mono text-xs">{{ \App\Support\ThaiId::format($group->receipt_tax_id) }}</div>
                                            <div>{{ $group->receipt_address }}</div>
                                        </div>
                                    @else
                                        <x-wirekit::callout intent="warning">ยังไม่ได้บันทึกข้อมูลใบเสร็จกลาง กรุณาบันทึกที่ส่วน "ข้อมูลสำหรับออกใบเสร็จรับเงิน (ข้อมูลกลาง)" หรือยกเลิกการเลือกเพื่อระบุแยกรายบุคคล</x-wirekit::callout>
                                    @endif
                                    @error('form.use_group_receipt')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
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

                            <div class="rounded-xl border border-slate-200 p-4">
                                <x-wirekit::data-list>
                                    <x-wirekit::data-list.item label="ผู้เข้าร่วม">
                                        {{ $form['title'] === 'อื่นๆ' ? $form['title_other'] : $form['title'] }}{{ $form['full_name'] }}
                                    </x-wirekit::data-list.item>
                                    <x-wirekit::data-list.item label="ตำแหน่ง" :value="$form['position']" />
                                    <x-wirekit::data-list.item label="โทรศัพท์" :value="$form['mobile_phone']" />
                                    <x-wirekit::data-list.item label="อาหาร">
                                        {{ $form['food_type'] === 'อื่นๆ' ? $form['food_type_other'] : $form['food_type'] }}
                                        @if ($form['food_allergy'])<span class="text-red-600">· แพ้ {{ $form['food_allergy'] }}</span>@endif
                                    </x-wirekit::data-list.item>
                                    <x-wirekit::data-list.item label="เสื้อ" :value="\App\Models\Participant::SHIRT_SIZES[$form['shirt_size']] ?? '-'" />
                                </x-wirekit::data-list>
                            </div>

                            @unless ($group->pdpa_accepted_at)
                                <x-wirekit::callout intent="neutral" :icon="false">
                                    <x-wirekit::stack gap="sm">
                                        <p>{{ $pdpaText }}</p>
                                        <x-wirekit::checkbox wire:model="pdpa" label="ยินยอมและยืนยัน" />
                                    </x-wirekit::stack>
                                </x-wirekit::callout>
                            @endunless
                            @error('pdpa')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </x-wirekit::stack>
                    @endif
                </form>
            </x-wirekit::stack>
        </x-wirekit::modal.body>

        <x-wirekit::modal.footer>
            <div class="mr-auto text-sm text-slate-500">ค่าลงทะเบียน: <span class="font-semibold text-blue-700">{{ number_format($this->formFee, 2) }} บาท</span></div>
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
