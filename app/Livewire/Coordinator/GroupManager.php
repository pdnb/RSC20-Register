<?php

namespace App\Livewire\Coordinator;

use App\Enums\Attendance;
use App\Enums\Section;
use App\Livewire\Coordinator\Concerns\ResolvesGroup;
use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\ParticipantType;
use App\Models\RegistrationGroup;
use App\Models\Setting;
use App\Services\FeeCalculator;
use App\Support\Images;
use App\Support\ThaiId;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('รายชื่อผู้เข้าร่วม')]
class GroupManager extends Component
{
    use ResolvesGroup;
    use WithFileUploads;

    /**
     * Rule keys each wizard step owns, so a step validates only its own fields.
     *
     * @var array<int, list<string>>
     */
    protected const STEP_FIELDS = [
        1 => ['form.section', 'form.position', 'form.attendance', 'form.title', 'form.title_other', 'form.full_name', 'form.mobile_phone', 'form.national_id'],
        2 => ['form.food_type', 'form.food_type_other', 'form.food_allergy', 'form.shirt_size', 'photo'],
        3 => ['form.use_group_receipt', 'form.receipt_name', 'form.receipt_address', 'form.receipt_tax_id', 'pdpa'],
    ];

    public ?int $editingId = null;

    public int $step = 1;

    public bool $pdpa = false;

    /** @var array<string, mixed> */
    public array $form = [];

    public $photo = null;

    /** @var array{name: string, address: string, tax_id: string} Group-wide receipt info */
    public array $receipt = ['name' => '', 'address' => '', 'tax_id' => ''];

    public function mount(?RegistrationGroup $group = null): void
    {
        $this->resolveGroup($group);
        $this->pdpa = (bool) $this->group->pdpa_accepted_at;
        $this->receipt = [
            'name' => (string) $this->group->receipt_name,
            'address' => (string) $this->group->receipt_address,
            'tax_id' => (string) $this->group->receipt_tax_id,
        ];
        $this->resetForm(Section::Executive->value);
    }

    public function layout(): string
    {
        return auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.app';
    }

    protected function resetForm(string $section): void
    {
        $this->form = [
            'section' => $section,
            'attendance' => Attendance::Conference->value,
            'position' => $section === Section::Member->value ? 'บุคลากร' : '',
            'title' => 'นาย',
            'title_other' => '',
            'full_name' => '',
            'mobile_phone' => '',
            'national_id' => '',
            'food_type' => Participant::FOOD_TYPES[0],
            'food_type_other' => '',
            'food_allergy' => '',
            'shirt_size' => '',
            'use_group_receipt' => true,
            'receipt_name' => '',
            'receipt_address' => '',
            'receipt_tax_id' => '',
            'badge_photo_path' => null,
        ];
        $this->photo = null;
        $this->step = 1;
        $this->resetValidation();
    }

    public function nextStep(): void
    {
        $this->form['mobile_phone'] = preg_replace('/\D/', '', (string) $this->form['mobile_phone']);
        $this->validate(array_intersect_key($this->rules(), array_flip(self::STEP_FIELDS[$this->step])));

        $this->step = min($this->step + 1, count(self::STEP_FIELDS));
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    /** Jump back to a finished step; moving forward always goes through nextStep(). */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step < $this->step) {
            $this->step = $step;
        }
    }

    /**
     * Picking a board game attendance moves the form off a position whose team places are all taken,
     * so the attendance chosen first never leaves a disabled position selected.
     */
    public function updatedForm(mixed $value, string $key): void
    {
        if ($key !== 'attendance' || ! $this->formAttendance()->includesBoardGame()) {
            return;
        }

        $seatsLeft = $this->boardGameSeatsLeft;

        if (($seatsLeft[Participant::MEMBER_POSITIONS[$this->form['position']] ?? ''] ?? 0) > 0) {
            return;
        }

        $openPosition = collect(Participant::MEMBER_POSITIONS)->search(fn (string $code) => $seatsLeft[$code] > 0);

        if ($openPosition !== false) {
            $this->form['position'] = $openPosition;
        }
    }

    public function fillReceiptFromParticipant(): void
    {
        $title = $this->form['title'] === 'อื่นๆ' ? trim((string) $this->form['title_other']) : $this->form['title'];

        $this->form['use_group_receipt'] = false;
        $this->form['receipt_name'] = $title.trim((string) $this->form['full_name']);
        $this->form['receipt_tax_id'] = $this->form['national_id'];
        $this->resetValidation(['form.receipt_name', 'form.receipt_tax_id']);
    }

    public function create(string $section): void
    {
        abort_unless($this->canEdit(), 403);
        $this->editingId = null;
        $this->resetForm($section);
        $this->dispatch('wirekit-modal-show', name: 'participant-form');
    }

    public function edit(int $id): void
    {
        abort_unless($this->canEditExistingParticipants(), 403);
        $p = $this->group->participants()->findOrFail($id);

        $this->resetForm($p->section->value);
        $this->editingId = $p->id;

        $titleKnown = in_array($p->title, Participant::TITLES, true);
        $foodKnown = in_array($p->food_type, Participant::FOOD_TYPES, true);

        $this->form = array_merge($this->form, [
            'position' => $p->position,
            'attendance' => $p->attendance->value,
            'title' => $titleKnown ? $p->title : 'อื่นๆ',
            'title_other' => $titleKnown ? '' : $p->title,
            'full_name' => $p->full_name,
            'mobile_phone' => $p->mobile_phone,
            'national_id' => $p->national_id,
            'food_type' => $p->food_type === null ? Participant::FOOD_TYPES[0] : ($foodKnown ? $p->food_type : 'อื่นๆ'),
            'food_type_other' => $foodKnown || $p->food_type === null ? '' : $p->food_type,
            'food_allergy' => (string) $p->food_allergy,
            'shirt_size' => (string) $p->shirt_size,
            'use_group_receipt' => $p->usesGroupReceipt(),
            'receipt_name' => (string) $p->receipt_name,
            'receipt_address' => (string) $p->receipt_address,
            'receipt_tax_id' => (string) $p->receipt_tax_id,
            'badge_photo_path' => $p->badge_photo_path,
        ]);

        $this->dispatch('wirekit-modal-show', name: 'participant-form');
    }

    protected function rules(): array
    {
        $member = $this->form['section'] === Section::Member->value;
        $ownReceipt = ! $this->form['use_group_receipt'];
        $attendsConference = $this->formAttendance()->includesConference();

        $conferenceRules = [
            'form.food_type' => ['required', Rule::in([...Participant::FOOD_TYPES, 'อื่นๆ'])],
            'form.food_type_other' => ['required_if:form.food_type,อื่นๆ', 'nullable', 'string', 'max:100'],
            'form.food_allergy' => ['nullable', 'string', 'max:255'],
            'form.shirt_size' => ['required', Rule::in(array_keys(Participant::SHIRT_SIZES))],
        ];

        $receiptRules = $ownReceipt ? [
            'form.receipt_name' => ['required', 'string', 'max:200'],
            'form.receipt_address' => ['required', 'string', 'max:500'],
            'form.receipt_tax_id' => ['required', $this->taxIdRule()],
        ] : [
            'form.use_group_receipt' => [function ($attr, $value, $fail) {
                if (! $this->group->hasReceiptInfo()) {
                    $fail('กรุณาบันทึกข้อมูลใบเสร็จกลางของมหาวิทยาลัยก่อน หรือระบุข้อมูลใบเสร็จแยกรายบุคคล');
                }
            }],
        ];

        return [
            'form.section' => ['required', Rule::enum(Section::class)],
            'form.position' => $member
                ? ['required', Rule::in(array_keys(Participant::MEMBER_POSITIONS))]
                : ['required', 'string', 'max:150'],
            'form.attendance' => $member
                ? ['required', Rule::enum(Attendance::class), $this->boardGameLimitRule()]
                : ['nullable'],
            'form.title' => ['required', Rule::in([...Participant::TITLES, 'อื่นๆ'])],
            'form.title_other' => ['required_if:form.title,อื่นๆ', 'nullable', 'string', 'max:50'],
            'form.full_name' => ['required', 'string', 'max:150'],
            'form.mobile_phone' => ['required', 'regex:/^0\d{9}$/'],
            'form.national_id' => ['required', function ($attr, $value, $fail) {
                if (! ThaiId::isValid($value)) {
                    return $fail('เลขประจำตัวประชาชนไม่ถูกต้อง');
                }
                $dup = Participant::query()->where('national_id_hash', ThaiId::hash($value))
                    ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
                    ->with('group.university')->first();
                if ($dup) {
                    $fail('เลขประจำตัวประชาชนนี้ลงทะเบียนแล้ว ('.$dup->group->university->name.')');
                }
            }],
            // Board game only: no fee, food or shirt, so none of those fields apply.
            ...($attendsConference ? $conferenceRules : []),
            ...($attendsConference ? $receiptRules : []),
            'photo' => [Rule::requiredIf(! $this->form['badge_photo_path']), 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'pdpa' => ['accepted'],
        ];
    }

    /** Executives always attend the conference; staff and students choose. */
    protected function formAttendance(): Attendance
    {
        if (($this->form['section'] ?? null) !== Section::Member->value) {
            return Attendance::Conference;
        }

        return Attendance::tryFrom((string) ($this->form['attendance'] ?? '')) ?? Attendance::Conference;
    }

    /** Each university fields at most 1 staff member and 6 students in the board game competition. */
    protected function boardGameLimitRule(): \Closure
    {
        return function ($attr, $value, $fail) {
            $typeCode = Participant::MEMBER_POSITIONS[$this->form['position'] ?? ''] ?? null;

            if (! $typeCode || ! $this->formAttendance()->includesBoardGame()) {
                return;
            }

            $limit = Participant::BOARD_GAME_LIMITS[$typeCode];
            $taken = FeeCalculator::boardGameTeam($this->group, $this->editingId)[$typeCode];

            if ($taken >= $limit) {
                $fail('ทีมแข่งขันบอร์ดเกมของมหาวิทยาลัยมี'.$this->form['position'].'ครบ '.$limit.' คนแล้ว');
            }
        };
    }

    protected function taxIdRule(): \Closure
    {
        return function ($attr, $value, $fail) {
            if (! ThaiId::isValid($value)) {
                $fail('เลขประจำตัวผู้เสียภาษีไม่ถูกต้อง');
            }
        };
    }

    protected function validationAttributes(): array
    {
        return [
            'form.position' => 'ตำแหน่ง',
            'form.attendance' => 'รูปแบบการลงทะเบียน',
            'form.title' => 'คำนำหน้า',
            'form.title_other' => 'คำนำหน้า (ระบุ)',
            'form.full_name' => 'ชื่อ-นามสกุล',
            'form.mobile_phone' => 'หมายเลขโทรศัพท์มือถือ',
            'form.national_id' => 'เลขประจำตัวประชาชน',
            'form.food_type' => 'ประเภทอาหาร',
            'form.food_type_other' => 'ประเภทอาหาร (ระบุ)',
            'form.shirt_size' => 'ขนาดไซซ์เสื้อ',
            'form.receipt_name' => 'ชื่อผู้รับใบเสร็จ',
            'form.receipt_address' => 'ที่อยู่สำหรับออกใบเสร็จ',
            'form.receipt_tax_id' => 'เลขประจำตัวผู้เสียภาษี',
            'receipt.name' => 'ชื่อผู้รับใบเสร็จ',
            'receipt.address' => 'ที่อยู่สำหรับออกใบเสร็จ',
            'receipt.tax_id' => 'เลขประจำตัวผู้เสียภาษี',
            'photo' => 'รูปภาพสำหรับทำป้ายชื่อ',
            'pdpa' => 'การยินยอม PDPA',
        ];
    }

    protected function messages(): array
    {
        return [
            'form.mobile_phone.regex' => 'หมายเลขโทรศัพท์มือถือต้องเป็นตัวเลข 10 หลัก ขึ้นต้นด้วย 0',
            'pdpa.accepted' => 'กรุณายืนยันการยินยอมให้เก็บข้อมูลส่วนบุคคล',
            '*.required_if' => 'กรุณาระบุ :attribute',
        ];
    }

    public function save(bool $addAnother = false): void
    {
        $fullAccess = $this->canEdit();
        abort_unless($this->editingId ? $this->canEditExistingParticipants() : $fullAccess, 403);
        $addAnother = $addAnother && $fullAccess;

        $this->form['mobile_phone'] = preg_replace('/\D/', '', (string) $this->form['mobile_phone']);

        try {
            $this->validate();

            if (! $fullAccess) {
                $this->ensureFeeTypeUnchanged();
            }
        } catch (ValidationException $e) {
            $this->step = $this->firstStepWithErrors(array_keys($e->errors()));
            $this->dispatch('wirekit:focus-invalid');

            throw $e;
        }

        $f = $this->form;
        $attendance = $this->formAttendance();
        $conference = $attendance->includesConference();
        $ownReceipt = $conference && ! $f['use_group_receipt'];
        $title = $f['title'] === 'อื่นๆ' ? trim($f['title_other']) : $f['title'];
        $typeCode = $f['section'] === Section::Executive->value
            ? ParticipantType::EXECUTIVE
            : Participant::MEMBER_POSITIONS[$f['position']];

        $participant = $this->editingId
            ? $this->group->participants()->findOrFail($this->editingId)
            : new Participant(['registration_group_id' => $this->group->id]);

        $participant->fill([
            'section' => $f['section'],
            'attendance' => $attendance,
            'participant_type_id' => ParticipantType::byCode($typeCode)->id,
            'position' => trim($f['position']),
            'title' => $title,
            'full_name' => trim($f['full_name']),
            'mobile_phone' => $f['mobile_phone'],
            'national_id' => ThaiId::normalize($f['national_id']),
            'food_type' => match (true) {
                ! $conference => null,
                $f['food_type'] === 'อื่นๆ' => trim($f['food_type_other']),
                default => $f['food_type'],
            },
            'food_allergy' => $conference ? (trim((string) $f['food_allergy']) ?: null) : null,
            'shirt_size' => $conference ? $f['shirt_size'] : null,
            'receipt_name' => $ownReceipt ? trim($f['receipt_name']) : null,
            'receipt_address' => $ownReceipt ? trim($f['receipt_address']) : null,
            'receipt_tax_id' => $ownReceipt ? ThaiId::normalize($f['receipt_tax_id']) : null,
        ]);
        $participant->save();

        if ($this->photo) {
            $path = 'badges/'.$participant->code.'.jpg';
            Storage::disk('local')->put($path, Images::badge($this->photo->getRealPath()));
            $participant->forceFill(['badge_photo_path' => $path])->saveQuietly();
        }

        if (! $this->group->pdpa_accepted_at) {
            $this->group->forceFill(['pdpa_accepted_at' => now()])->save();
        }

        AuditLog::record($this->editingId ? 'participant.updated' : 'participant.created', $participant);

        if ($addAnother) {
            $this->editingId = null;
            $this->resetForm($f['section']);
        } else {
            $this->dispatch('wirekit-modal-close', name: 'participant-form');
        }
        $this->group->refresh();
        unset($this->summary, $this->boardGameSeatsLeft);
        $this->dispatch('wirekit-toast', variant: 'success', title: 'บันทึกแล้ว', message: $participant->displayName());
    }

    /**
     * While only unlocked for editing, a change may not move the participant to a different fee type,
     * nor into or out of board game only (which has no fee).
     *
     * @throws ValidationException
     */
    protected function ensureFeeTypeUnchanged(): void
    {
        $current = $this->group->participants()->findOrFail($this->editingId);
        $typeCode = $this->form['section'] === Section::Executive->value
            ? ParticipantType::EXECUTIVE
            : Participant::MEMBER_POSITIONS[$this->form['position']];

        if (ParticipantType::byCode($typeCode)->id !== $current->participant_type_id) {
            throw ValidationException::withMessages([
                'form.position' => 'ไม่สามารถเปลี่ยนประเภทผู้เข้าร่วมที่มีผลต่อค่าลงทะเบียนได้ในช่วงปลดล็อคแก้ไข',
            ]);
        }

        if ($this->formAttendance()->includesConference() !== $current->attendsConference()) {
            throw ValidationException::withMessages([
                'form.attendance' => 'ไม่สามารถเปลี่ยนรูปแบบการลงทะเบียนที่มีผลต่อค่าลงทะเบียนได้ในช่วงปลดล็อคแก้ไข',
            ]);
        }
    }

    public function toggleEditUnlock(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $unlocking = ! $this->group->isEditUnlocked();
        $this->group->forceFill(['edit_unlocked_at' => $unlocking ? now() : null])->save();

        AuditLog::record($unlocking ? 'group.edit_unlocked' : 'group.edit_locked', $this->group);
        $this->dispatch('wirekit-toast', variant: 'success',
            title: $unlocking ? 'ปลดล็อคให้แก้ไขรายชื่อแล้ว' : 'ล็อคการแก้ไขรายชื่อแล้ว',
            message: $this->group->university->name);
    }

    /**
     * @param  list<string>  $errorKeys
     */
    protected function firstStepWithErrors(array $errorKeys): int
    {
        foreach (self::STEP_FIELDS as $step => $fields) {
            if (array_intersect($fields, $errorKeys)) {
                return $step;
            }
        }

        return $this->step;
    }

    /** Shared receipt info used by every participant who has none of their own. */
    public function saveReceipt(): void
    {
        abort_unless($this->canEdit(), 403);

        $this->validate([
            'receipt.name' => ['required', 'string', 'max:200'],
            'receipt.address' => ['required', 'string', 'max:500'],
            'receipt.tax_id' => ['required', $this->taxIdRule()],
        ]);

        $this->group->forceFill([
            'receipt_name' => trim($this->receipt['name']),
            'receipt_address' => trim($this->receipt['address']),
            'receipt_tax_id' => ThaiId::normalize($this->receipt['tax_id']),
        ])->save();
        $this->receipt['tax_id'] = $this->group->receipt_tax_id;

        AuditLog::record('group.receipt_updated', $this->group);

        $this->dispatch('wirekit-toast', variant: 'success', title: 'บันทึกข้อมูลใบเสร็จแล้ว', message: $this->group->receipt_name);
    }

    public function delete(int $id): void
    {
        abort_unless($this->canEdit(), 403);
        $p = $this->group->participants()->findOrFail($id);
        AuditLog::record('participant.deleted', $p, ['name' => $p->displayName()]);
        $p->delete();

        $this->group->refresh();
        unset($this->summary, $this->boardGameSeatsLeft);
        $this->dispatch('wirekit-toast', variant: 'success', title: 'ลบแล้ว', message: $p->displayName());
    }

    #[Computed]
    public function summary(): array
    {
        return FeeCalculator::forGroup($this->group);
    }

    /**
     * Board game team places still open per member type code, not counting the participant being edited.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function boardGameSeatsLeft(): array
    {
        $taken = FeeCalculator::boardGameTeam($this->group, $this->editingId);

        return collect(Participant::BOARD_GAME_LIMITS)
            ->map(fn (int $limit, string $code) => max(0, $limit - $taken[$code]))
            ->all();
    }

    /** Fee the open form would add, for the live preview next to the form. */
    #[Computed]
    public function formFee(): float
    {
        $code = ($this->form['section'] ?? '') === Section::Executive->value
            ? ParticipantType::EXECUTIVE
            : (Participant::MEMBER_POSITIONS[$this->form['position'] ?? ''] ?? null);

        if (! $this->formAttendance()->includesConference()) {
            return 0.0;
        }

        return $code ? (float) ParticipantType::query()->where('code', $code)->value('fee') : 0.0;
    }

    public function render()
    {
        $participants = $this->group->participants()->with('type')->orderBy('id')->get();

        return view('livewire.coordinator.group-manager', [
            'executives' => $participants->where('section', Section::Executive),
            'members' => $participants->where('section', Section::Member),
            'types' => ParticipantType::query()->orderBy('sort_order')->get(),
            'editable' => $this->canEdit(),
            'editableExisting' => $this->canEditExistingParticipants(),
            'registrationOpen' => Setting::registrationOpen(),
            'pdpaText' => Setting::get('pdpa_text'),
        ])->layout($this->layout());
    }
}
