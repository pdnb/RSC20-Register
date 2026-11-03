<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\ParticipantType;
use App\Models\Setting;
use App\Support\Images;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('ตั้งค่าระบบและค่าลงทะเบียน')]
class Settings extends Component
{
    use WithFileUploads;

    public const BADGE_BACKGROUND_PATH = 'badges/background.jpg';

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

        $this->validate([
            'fees.*' => ['required', 'numeric', 'min:0', 'max:100000'],
            'settings.registration_opens_at' => ['nullable', 'date'],
            'settings.registration_closes_at' => ['nullable', 'date', 'after_or_equal:settings.registration_opens_at'],
            ...array_fill_keys(array_map(fn ($k) => 'settings.'.$k, $badgeKeys), ['required', 'numeric', 'between:0,100']),
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'fees.*' => 'ค่าลงทะเบียน', 'settings.registration_closes_at' => 'วันปิดรับสมัคร',
            ...array_fill_keys(array_map(fn ($k) => 'settings.'.$k, $badgeKeys), 'ตำแหน่งบนป้ายชื่อ'),
        ]);

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

    public function render()
    {
        $backgroundPath = Setting::get('badge_background_path');

        return view('livewire.admin.settings', [
            'types' => ParticipantType::query()->orderBy('sort_order')->get(),
            'badgeBackgroundUrl' => $backgroundPath && Storage::disk('local')->exists($backgroundPath)
                ? route('admin.badge-background', ['v' => Storage::disk('local')->lastModified($backgroundPath)])
                : null,
        ]);
    }
}
