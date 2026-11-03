<?php

namespace App\Livewire\Coordinator;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Coordinator\Concerns\ResolvesGroup;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\FeeCalculator;
use App\Support\Images;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('ชำระค่าลงทะเบียน')]
class Payment extends Component
{
    use ResolvesGroup;
    use WithFileUploads;

    public string $method = 'transfer';

    public $slip = null;

    public string $slip_reference = '';

    public string $transferred_at = '';

    public function mount(): void
    {
        $this->resolveGroup();
        $this->method = $this->group->payment_method?->value ?? PaymentMethod::Transfer->value;
        $this->slip_reference = (string) $this->group->slip_reference;
        $this->transferred_at = $this->group->transferred_at?->format('Y-m-d\TH:i') ?? '';
    }

    public function chooseOnsite(): void
    {
        $this->guardChangeable();

        $this->group->forceFill([
            'payment_method' => PaymentMethod::Onsite,
            'payment_status' => PaymentStatus::Pending,
            'reject_reason' => null,
        ])->save();

        AuditLog::record('payment.onsite_chosen', $this->group);
        $this->dispatch('wirekit-toast', variant: 'success', title: 'บันทึกแล้ว', message: 'เลือกชำระค่าลงทะเบียนหน้างาน');
    }

    public function submitSlip(): void
    {
        $this->guardChangeable();

        $this->validate([
            'slip' => [Rule::requiredIf(! $this->group->slip_path), 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'slip_reference' => ['nullable', 'string', 'max:100'],
            // The slip time is entered (and stored/displayed) as Thai wall-clock time, so compare it to "now" in Bangkok, not app UTC.
            'transferred_at' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:'.now('Asia/Bangkok')->format('Y-m-d H:i:s')],
        ], attributes: [
            'slip' => 'สลิปการโอนเงิน',
            'slip_reference' => 'เลขอ้างอิง',
            'transferred_at' => 'วันเวลาที่โอน',
        ]);

        if ($this->slip) {
            if ($this->group->slip_path) {
                Storage::disk('local')->delete($this->group->slip_path);
            }
            // Re-encode: strips EXIF and keeps a sane size for the finance-report appendix.
            $path = 'slips/group-'.$this->group->id.'-'.now()->format('YmdHis').'.jpg';
            Storage::disk('local')->put($path, Images::fit($this->slip->getRealPath(), 1600));
            $this->group->slip_path = $path;
        }

        $this->group->forceFill([
            'payment_method' => PaymentMethod::Transfer,
            'payment_status' => PaymentStatus::Submitted,
            'slip_reference' => trim($this->slip_reference) ?: null,
            'transferred_at' => $this->transferred_at,
            'slip_submitted_at' => now(),
            'reject_reason' => null,
        ])->save();

        $this->slip = null;
        AuditLog::record('payment.slip_submitted', $this->group, ['amount' => (float) $this->group->total_amount]);
        $this->dispatch('wirekit-toast', variant: 'success', title: 'ส่งสลิปแล้ว', message: 'รอผู้ดูแลตรวจสอบ');
    }

    private function guardChangeable(): void
    {
        abort_if($this->group->isPaid(), 403, 'ชำระเงินแล้ว');
        abort_if($this->group->participants()->doesntExist(), 422, 'ยังไม่มีรายชื่อผู้เข้าร่วม');
    }

    public function render()
    {
        return view('livewire.coordinator.payment', [
            'summary' => FeeCalculator::forGroup($this->group),
            'bank' => [
                'name' => Setting::get('bank_name'),
                'account_name' => Setting::get('bank_account_name'),
                'account_no' => Setting::get('bank_account_no'),
            ],
        ]);
    }
}
