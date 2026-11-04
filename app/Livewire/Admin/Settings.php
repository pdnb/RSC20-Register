<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\ParticipantType;
use App\Models\Setting;
use App\Support\Images;
use App\Support\ParticipantCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('ตั้งค่าระบบและค่าลงทะเบียน')]
class Settings extends Component
{
    use WithFileUploads;

    public const BADGE_BACKGROUND_PATH = 'badges/background.jpg';

    /**
     * The sub-sidebar sections: label, icon, and the field prefixes each one holds.
     *
     * @var array<string, array{0: string, 1: string, 2: list<string>}>
     */
    private const SECTIONS = [
        'event' => ['งานและช่วงเวลา', 'calendar', ['settings.event_', 'settings.registration_']],
        'fees' => ['ค่าลงทะเบียน', 'coins', ['fees.']],
        'code' => ['รหัสผู้เข้าร่วม', 'hash', ['settings.participant_code_']],
        'bank' => ['บัญชีรับโอน', 'billing', ['settings.bank_']],
        'finance' => ['รายงาน / PDPA', 'file-text', ['settings.finance_', 'settings.pdpa_']],
        'badge' => ['ป้ายชื่อ', 'badge', ['settings.badge_', 'badgeBackground']],
    ];

    #[Url]
    public string $section = 'event';

    /** @var array<int, string> participant_type_id → fee */
    public array $fees = [];

    /** @var array<string, string|null> */
    public array $settings = [];

    /** @var TemporaryUploadedFile|null */
    public $badgeBackground = null;

    public function mount(): void
    {
        $this->fees = ParticipantType::query()->pluck('fee', 'id')->map(fn ($f) => (string) $f)->all();

        foreach (array_diff(array_keys(Setting::DEFAULTS), Setting::MANAGED) as $key) {
            $this->settings[$key] = (string) Setting::get($key);
        }
    }

    public function save(): void
    {
        $this->settings = array_diff_key($this->settings, array_flip(Setting::MANAGED));

        $badgeKeys = ['badge_photo_left', 'badge_photo_top', 'badge_photo_width', 'badge_photo_height', 'badge_text_top'];

        try {
            $this->validate([
                'fees.*' => ['required', 'numeric', 'min:0', 'max:100000'],
                'settings.registration_opens_at' => ['nullable', 'date'],
                'settings.registration_closes_at' => ['nullable', 'date', 'after_or_equal:settings.registration_opens_at'],
                ...array_fill_keys(array_map(fn ($k) => 'settings.'.$k, $badgeKeys), ['required', 'numeric', 'between:0,100']),
                // 12-char prefix + 8 digits fits the 20-char participants.code column
                'settings.participant_code_prefix' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,12}$/'],
                'settings.participant_code_digits' => ['required', 'integer', 'between:1,8'],
                'settings.participant_code_start' => ['required', 'integer', 'min:1', 'max:99999999'],
                'settings.*' => ['nullable', 'string', 'max:2000'],
            ], attributes: [
                'fees.*' => 'ค่าลงทะเบียน', 'settings.registration_closes_at' => 'วันปิดรับสมัคร',
                ...array_fill_keys(array_map(fn ($k) => 'settings.'.$k, $badgeKeys), 'ตำแหน่งบนป้ายชื่อ'),
                'settings.participant_code_prefix' => 'คำนำหน้ารหัส',
                'settings.participant_code_digits' => 'จำนวนหลัก',
                'settings.participant_code_start' => 'เลขเริ่มต้น',
            ], messages: [
                'settings.participant_code_prefix.regex' => 'คำนำหน้ารหัสใช้ได้เฉพาะ A–Z, 0–9, - และ _ ไม่เกิน 12 ตัวอักษร',
            ]);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            $this->section = $this->sectionWithErrors() ?? $this->section;
            $this->dispatch('wirekit:focus-invalid');

            throw $e;
        }

        $before = ParticipantType::query()->pluck('fee', 'id')->all();
        foreach ($this->fees as $id => $fee) {
            ParticipantType::query()->whereKey($id)->update(['fee' => $fee]);
        }

        Setting::put($this->settings);
        AuditLog::record('settings.updated', null, ['fees_before' => $before, 'fees_after' => $this->fees]);

        $this->dispatch('wirekit-toast', variant: 'success', title: 'บันทึกการตั้งค่าแล้ว', message: 'ค่าลงทะเบียนใหม่มีผลกับรายชื่อที่เพิ่มหลังจากนี้');
    }

    /** Stored as soon as it is picked, so the preview below shows the real file. */
    public function updatedBadgeBackground(): void
    {
        $this->validate(
            ['badgeBackground' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240']],
            attributes: ['badgeBackground' => 'พื้นหลังป้ายชื่อ'],
        );

        Storage::disk('local')->put(self::BADGE_BACKGROUND_PATH, Images::fit($this->badgeBackground->getRealPath(), 2400, 90));
        Setting::put(['badge_background_path' => self::BADGE_BACKGROUND_PATH]);
        AuditLog::record('settings.badge_background', null, ['action' => 'uploaded']);

        $this->badgeBackground = null;
        $this->dispatch('wirekit-toast', variant: 'success', title: 'อัปโหลดพื้นหลังป้ายชื่อแล้ว');
    }

    public function removeBadgeBackground(): void
    {
        Storage::disk('local')->delete(self::BADGE_BACKGROUND_PATH);
        Setting::put(['badge_background_path' => null]);
        AuditLog::record('settings.badge_background', null, ['action' => 'removed']);
    }

    public function showSection(string $key): void
    {
        if (array_key_exists($key, self::SECTIONS)) {
            $this->section = $key;
        }
    }

    /** The first section holding a rejected field, so the field is on screen when focus moves to it. */
    private function sectionWithErrors(): ?string
    {
        $errorFields = $this->getErrorBag()->keys();

        foreach (self::SECTIONS as $key => [, , $prefixes]) {
            if (collect($errorFields)->contains(fn (string $field) => Str::startsWith($field, $prefixes))) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Each section with the count of rejected fields it holds, so an error in a hidden section is still seen.
     *
     * @return list<array{key: string, label: string, icon: string, badge: int|null}>
     */
    private function sections(): array
    {
        $errorFields = $this->getErrorBag()->keys();

        return collect(self::SECTIONS)->map(fn (array $section, string $key) => [
            'key' => $key,
            'label' => $section[0],
            'icon' => $section[1],
            'badge' => collect($errorFields)->filter(fn (string $field) => Str::startsWith($field, $section[2]))->count() ?: null,
        ])->values()->all();
    }

    /** The next code under the format being typed, or null while it is not yet valid. */
    private function codePreview(): ?string
    {
        $prefix = (string) ($this->settings['participant_code_prefix'] ?? '');
        $digits = $this->settings['participant_code_digits'] ?? '';
        $start = $this->settings['participant_code_start'] ?? '';

        if (! preg_match('/^[A-Za-z0-9_-]{1,12}$/', $prefix) || ! ctype_digit((string) $digits) || ! ctype_digit((string) $start)
            || $digits < 1 || $digits > 8 || $start < 1) {
            return null;
        }

        return ParticipantCode::preview($prefix, (int) $digits, (int) $start);
    }

    public function render()
    {
        $backgroundPath = Setting::get('badge_background_path');

        return view('livewire.admin.settings', [
            'sections' => $this->sections(),
            'types' => ParticipantType::query()->orderBy('sort_order')->get(),
            'codePreview' => $this->codePreview(),
            'badgeBackgroundUrl' => $backgroundPath && Storage::disk('local')->exists($backgroundPath)
                ? route('admin.badge-background', ['v' => Storage::disk('local')->lastModified($backgroundPath)])
                : null,
        ]);
    }
}
