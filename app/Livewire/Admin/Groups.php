<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\RegistrationGroup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('ตรวจสลิป / กลุ่มลงทะเบียน')]
class Groups extends Component
{
    #[Url]
    public string $status = 'submitted';

    public ?int $reviewId = null;

    public string $rejectReason = '';

    public function review(int $id): void
    {
        $this->reviewId = $id;
        $this->rejectReason = '';
        $this->resetValidation();
        $this->dispatch('wirekit-modal-show', name: 'review-slip');
    }

    public function approve(): void
    {
        $group = RegistrationGroup::query()->findOrFail($this->reviewId);
        abort_unless($group->payment_status === PaymentStatus::Submitted, 422);

        $group->forceFill([
            'payment_status' => PaymentStatus::Approved,
            'paid_amount' => $group->total_amount,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'reject_reason' => null,
        ])->save();

        AuditLog::record('payment.approved', $group, ['amount' => (float) $group->total_amount]);
        $this->dispatch('wirekit-modal-close', name: 'review-slip');
        $this->dispatch('wirekit-toast', variant: 'success', title: 'อนุมัติสลิปแล้ว', message: $group->university->name);
    }

    public function reject(): void
    {
        $this->validate(['rejectReason' => ['required', 'string', 'max:255']], attributes: ['rejectReason' => 'เหตุผล']);
        $group = RegistrationGroup::query()->findOrFail($this->reviewId);
        abort_unless($group->payment_status === PaymentStatus::Submitted, 422);

        $group->forceFill([
            'payment_status' => PaymentStatus::Rejected,
            'reject_reason' => 'สลิปไม่ผ่าน: '.$this->rejectReason,
        ])->save();

        AuditLog::record('payment.rejected', $group, ['reason' => $this->rejectReason]);
        $this->dispatch('wirekit-modal-close', name: 'review-slip');
        $this->dispatch('wirekit-toast', variant: 'warning', title: 'ปฏิเสธสลิปแล้ว', message: $group->university->name);
    }

    public function render()
    {
        return view('livewire.admin.groups', [
            'groups' => RegistrationGroup::query()
                ->with(['university', 'approver'])->withCount('participants')
                ->when($this->status !== 'all', fn ($q) => $q->where('payment_status', $this->status))
                ->orderByDesc('slip_submitted_at')->orderBy('university_id')
                ->get(),
            'counts' => RegistrationGroup::query()->selectRaw('payment_status, count(*) c')->groupBy('payment_status')->pluck('c', 'payment_status'),
            'reviewing' => $this->reviewId ? RegistrationGroup::query()->with('university')->find($this->reviewId) : null,
        ]);
    }
}
