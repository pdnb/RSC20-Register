<div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">รายชื่อผู้เข้าร่วม</h1>
            <p class="text-sm text-slate-500">{{ $group->university->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-wirekit::badge :intent="$group->payment_status->intent()">{{ $group->payment_status->label() }}</x-wirekit::badge>
            @if ($group->payment_method)
                <x-wirekit::badge intent="info">{{ $group->payment_method->label() }}</x-wirekit::badge>
            @endif
        </div>
    </div>

    @if (! $registrationOpen && ! auth()->user()->isAdmin())
        <x-wirekit::callout intent="warning">ขณะนี้อยู่นอกช่วงเวลารับลงทะเบียน จึงแก้ไขรายชื่อไม่ได้</x-wirekit::callout>
    @elseif ($group->isPaid() && ! auth()->user()->isAdmin())
        <x-wirekit::callout intent="info">ชำระเงินเรียบร้อยแล้ว หากต้องการแก้ไขรายชื่อ กรุณาติดต่อผู้ดูแลระบบ</x-wirekit::callout>
    @endif
    @if ($group->reject_reason && ! $group->isPaid())
        <x-wirekit::callout intent="danger">{{ $group->reject_reason }}</x-wirekit::callout>
    @endif

    {{-- Fee summary --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($types as $type)
            @php($line = $this->summary['lines']->firstWhere('type', $type->name_th))
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs text-slate-500">{{ $type->name_th }} · {{ number_format($type->fee, 2) }} บาท/คน</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900">{{ $line['count'] ?? 0 }} <span class="text-sm font-normal text-slate-500">คน</span></div>
            </div>
        @endforeach
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <div class="text-xs text-blue-700">ยอดรวมที่ต้องชำระ ({{ $this->summary['count'] }} คน)</div>
            <div class="mt-1 text-2xl font-semibold text-blue-800">{{ number_format($this->summary['total'], 2) }} <span class="text-sm font-normal">บาท</span></div>
        </div>
    </div>

    @foreach ([
        ['section' => \App\Enums\Section::Executive, 'rows' => $executives],
        ['section' => \App\Enums\Section::Member, 'rows' => $members],
    ] as $block)
        <x-wirekit::card>
            <x-wirekit::card.header>
                <div class="flex w-full flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold text-slate-900">{{ $block['section']->label() }} <span class="text-sm font-normal text-slate-500">({{ $block['rows']->count() }} คน)</span></h2>
                    @if ($editable)
                        <x-wirekit::button size="sm" wire:click="create('{{ $block['section']->value }}')">+ เพิ่มรายชื่อ</x-wirekit::button>
                    @endif
                </div>
            </x-wirekit::card.header>

            @if ($block['rows']->isEmpty())
                <x-wirekit::card.body>
                    <p class="py-6 text-center text-sm text-slate-500">ยังไม่มีรายชื่อในส่วนนี้</p>
                </x-wirekit::card.body>
            @else
                <x-wirekit::table hoverable stack-below="md">
                    <x-wirekit::table.head>
                        <x-wirekit::table.row>
                            <x-wirekit::table.th>รูป</x-wirekit::table.th>
                            <x-wirekit::table.th>ชื่อ-นามสกุล / ตำแหน่ง</x-wirekit::table.th>
                            <x-wirekit::table.th>โทรศัพท์</x-wirekit::table.th>
                            <x-wirekit::table.th>เลขบัตร</x-wirekit::table.th>
                            <x-wirekit::table.th>อาหาร / แพ้อาหาร</x-wirekit::table.th>
                            <x-wirekit::table.th>เสื้อ</x-wirekit::table.th>
                            <x-wirekit::table.th align="right">ค่าลงทะเบียน</x-wirekit::table.th>
                            @if ($editable)<x-wirekit::table.th></x-wirekit::table.th>@endif
                        </x-wirekit::table.row>
                    </x-wirekit::table.head>
                    <x-wirekit::table.body>
                        @foreach ($block['rows'] as $p)
                            <x-wirekit::table.row wire:key="p-{{ $p->id }}">
                                <x-wirekit::table.td>
                                    @if ($p->badge_photo_path)
                                        <img src="{{ route('files.badge', $p) }}?v={{ $p->updated_at->timestamp }}" alt="" class="size-10 rounded-lg object-cover">
                                    @else
                                        <span class="flex size-10 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">ไม่มี</span>
                                    @endif
                                </x-wirekit::table.td>
                                <x-wirekit::table.td>
                                    <div class="font-medium text-slate-900">{{ $p->displayName() }}</div>
                                    <div class="text-xs text-slate-500">{{ $p->position }} · {{ $p->code }}</div>
                                </x-wirekit::table.td>
                                <x-wirekit::table.td>{{ $p->mobile_phone }}</x-wirekit::table.td>
                                <x-wirekit::table.td><span class="font-mono text-xs">{{ $p->maskedNationalId() }}</span></x-wirekit::table.td>
                                <x-wirekit::table.td>
                                    <div>{{ $p->food_type }}</div>
                                    @if ($p->food_allergy)<div class="text-xs text-red-600">แพ้: {{ $p->food_allergy }}</div>@endif
                                </x-wirekit::table.td>
                                <x-wirekit::table.td>{{ $p->shirt_size }}</x-wirekit::table.td>
                                <x-wirekit::table.td align="right">{{ number_format($p->fee_amount, 2) }}</x-wirekit::table.td>
                                @if ($editable)
                                    <x-wirekit::table.td align="right">
                                        <div class="flex justify-end gap-1">
                                            <x-wirekit::button size="sm" intent="neutral" surface="ghost" wire:click="edit({{ $p->id }})">แก้ไข</x-wirekit::button>
                                            <x-wirekit::button size="sm" intent="danger" surface="ghost"
                                                wire:click="delete({{ $p->id }})"
                                                wire:confirm="ลบ {{ $p->displayName() }} ออกจากรายชื่อ?">ลบ</x-wirekit::button>
                                        </div>
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
        <div class="flex justify-end">
            <x-wirekit::button :href="route('coordinator.payment')">ไปหน้าชำระเงิน ({{ number_format($this->summary['total'], 2) }} บาท) →</x-wirekit::button>
        </div>
    @endif

    {{-- Add / edit form --}}
    <x-wirekit::modal name="participant-form" size="xl">
        <x-wirekit::modal.header>
            {{ $editingId ? 'แก้ไขรายชื่อ' : 'เพิ่มรายชื่อ' }} · {{ \App\Enums\Section::from($form['section'])->label() }}
        </x-wirekit::modal.header>

        <x-wirekit::modal.body>
            <form wire:submit="save" id="participant-form" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        @if ($form['section'] === 'executive')
                            <x-wirekit::input wire:model="form.position" label="ตำแหน่ง" placeholder="เช่น อธิการบดี, รองอธิการบดีฝ่ายกิจการนักศึกษา" required />
                        @else
                            <x-wirekit::select wire:model.live="form.position" label="ตำแหน่ง" required
                                :options="['บุคลากร' => 'บุคลากร', 'นักศึกษา' => 'นักศึกษา']" />
                        @endif
                    </div>

                    <x-wirekit::select wire:model.live="form.title" label="คำนำหน้า" required
                        :options="array_combine([...\App\Models\Participant::TITLES, 'อื่นๆ'], [...\App\Models\Participant::TITLES, 'อื่นๆ (ระบุ)'])" />
                    @if ($form['title'] === 'อื่นๆ')
                        <x-wirekit::input wire:model="form.title_other" label="คำนำหน้า (ระบุ)" required />
                    @else
                        <div class="hidden sm:block"></div>
                    @endif

                    <div class="sm:col-span-2">
                        <x-wirekit::input wire:model="form.full_name" label="ชื่อ-นามสกุล" required />
                    </div>
                    <x-wirekit::input wire:model="form.mobile_phone" label="หมายเลขโทรศัพท์มือถือ" inputmode="numeric" maxlength="10" placeholder="0812345678" required />
                    <x-wirekit::input wire:model="form.national_id" label="เลขประจำตัวประชาชน (13 หลัก)" inputmode="numeric" maxlength="17" mono required />

                    <x-wirekit::select wire:model.live="form.food_type" label="ประเภทอาหาร" required
                        :options="array_combine([...\App\Models\Participant::FOOD_TYPES, 'อื่นๆ'], [...\App\Models\Participant::FOOD_TYPES, 'อื่นๆ (ระบุ)'])" />
                    @if ($form['food_type'] === 'อื่นๆ')
                        <x-wirekit::input wire:model="form.food_type_other" label="ประเภทอาหาร (ระบุ)" required />
                    @else
                        <div class="hidden sm:block"></div>
                    @endif
                    <x-wirekit::input wire:model="form.food_allergy" label="อาหารที่แพ้" placeholder="ไม่มี ให้เว้นว่าง" />
                    <x-wirekit::select wire:model="form.shirt_size" label="ขนาดไซซ์เสื้อ" required
                        :options="array_combine(\App\Models\Participant::SHIRT_SIZES, \App\Models\Participant::SHIRT_SIZES)" />
                </div>

                {{-- Badge photo --}}
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="mb-2 text-sm font-medium text-slate-800">รูปภาพสำหรับทำป้ายชื่อ <span class="font-normal text-slate-500">(jpg/png ไม่เกิน 2MB ระบบจะครอปเป็นสี่เหลี่ยมจัตุรัส)</span></div>
                    <div class="flex items-center gap-4">
                        @if ($photo && method_exists($photo, 'temporaryUrl') && in_array(strtolower($photo->getClientOriginalExtension()), ['jpg', 'jpeg', 'png']))
                            <img src="{{ $photo->temporaryUrl() }}" class="size-20 rounded-lg object-cover" alt="">
                        @elseif ($form['badge_photo_path'] && $editingId)
                            <img src="{{ route('files.badge', $editingId) }}?v={{ now()->timestamp }}" class="size-20 rounded-lg object-cover" alt="">
                        @else
                            <div class="flex size-20 items-center justify-center rounded-lg bg-slate-100 text-xs text-slate-400">ยังไม่มีรูป</div>
                        @endif
                        <div class="flex-1">
                            <input type="file" wire:model="photo" accept="image/jpeg,image/png"
                                class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                            <div wire:loading wire:target="photo" class="mt-1 text-xs text-slate-500">กำลังอัปโหลด…</div>
                            @error('photo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Receipt (issued per person) --}}
                <div class="space-y-4 rounded-xl border border-slate-200 p-4">
                    <div class="text-sm font-medium text-slate-800">ข้อมูลสำหรับออกใบเสร็จรับเงิน (ออกในนามบุคคล)</div>
                    <x-wirekit::checkbox wire:model.live="form.receipt_same" label="ใช้ชื่อและเลขประจำตัวประชาชนเดียวกับผู้เข้าร่วม" />
                    @unless ($form['receipt_same'])
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-wirekit::input wire:model="form.receipt_name" label="ชื่อผู้รับใบเสร็จ" required />
                            <x-wirekit::input wire:model="form.receipt_tax_id" label="เลขประจำตัวผู้เสียภาษี (13 หลัก)" inputmode="numeric" maxlength="17" mono required />
                        </div>
                    @endunless
                    <x-wirekit::textarea wire:model="form.receipt_address" label="ที่อยู่สำหรับออกใบเสร็จ" rows="2" required />
                </div>

                @unless ($group->pdpa_accepted_at)
                    <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="mb-3">{{ $pdpaText }}</p>
                        <x-wirekit::checkbox wire:model="pdpa" label="ยินยอมและยืนยัน" />
                    </div>
                @endunless
                @error('pdpa')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </form>
        </x-wirekit::modal.body>

        <x-wirekit::modal.footer>
            <div class="mr-auto text-sm text-slate-500">ค่าลงทะเบียน: <span class="font-semibold text-blue-700">{{ number_format($this->formFee, 2) }} บาท</span></div>
            <x-wirekit::modal.close>
                <x-wirekit::button intent="neutral" surface="ghost">ยกเลิก</x-wirekit::button>
            </x-wirekit::modal.close>
            <x-wirekit::button type="submit" form="participant-form" wire:loading.attr="disabled" wire:target="save,photo">บันทึก</x-wirekit::button>
        </x-wirekit::modal.footer>
    </x-wirekit::modal>
</div>
