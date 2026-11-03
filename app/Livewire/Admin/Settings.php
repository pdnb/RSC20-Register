<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\ParticipantType;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('ตั้งค่า / ค่าลงทะเบียน')]
class Settings extends Component
{
    /** @var array<int, string> participant_type_id → fee */
    public array $fees = [];

    /** @var array<string, string|null> */
    public array $settings = [];

    public function mount(): void
    {
        $this->fees = ParticipantType::query()->pluck('fee', 'id')->map(fn ($f) => (string) $f)->all();

        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $this->settings[$key] = (string) Setting::get($key);
        }
    }

    public function save(): void
    {
        $this->validate([
            'fees.*' => ['required', 'numeric', 'min:0', 'max:100000'],
            'settings.registration_opens_at' => ['nullable', 'date'],
            'settings.registration_closes_at' => ['nullable', 'date', 'after_or_equal:settings.registration_opens_at'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['fees.*' => 'ค่าลงทะเบียน', 'settings.registration_closes_at' => 'วันปิดรับสมัคร']);

        $before = ParticipantType::query()->pluck('fee', 'id')->all();
        foreach ($this->fees as $id => $fee) {
            ParticipantType::query()->whereKey($id)->update(['fee' => $fee]);
        }

        Setting::put($this->settings);
        AuditLog::record('settings.updated', null, ['fees_before' => $before, 'fees_after' => $this->fees]);

        $this->dispatch('wirekit-toast', variant: 'success', title: 'บันทึกการตั้งค่าแล้ว', message: 'ค่าลงทะเบียนใหม่มีผลกับรายชื่อที่เพิ่มหลังจากนี้');
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'types' => ParticipantType::query()->orderBy('sort_order')->get(),
        ]);
    }
}
