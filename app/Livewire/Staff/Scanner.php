<?php

namespace App\Livewire\Staff;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('เช็กอินหน้างาน')]
class Scanner extends Component
{
    public string $search = '';

    public ?int $participantId = null;

    /** 'ok' | 'already' | 'notfound' | null */
    public ?string $result = null;

    public function scanned(string $token): void
    {
        // The QR holds the ticket URL; accept either the URL or the bare token.
        $token = trim(str_contains($token, '/ticket/') ? substr($token, strrpos($token, '/ticket/') + 8) : $token);
        $participant = Participant::query()->where('qr_token', $token)->first();

        if (! $participant) {
            $this->participantId = null;
            $this->result = 'notfound';

            return;
        }

        $this->select($participant->id);
    }

    public function select(int $id): void
    {
        $this->participantId = $id;
        $this->search = '';
        $this->result = null;
    }

    public function checkIn(): void
    {
        $participant = Participant::query()->findOrFail($this->participantId);

        // Conditional update so two scanners can never check the same person in twice.
        $updated = Participant::query()
            ->whereKey($participant->id)
            ->whereNull('checked_in_at')
            ->update(['checked_in_at' => now(), 'checked_in_by' => auth()->id()]);

        if (! $updated) {
            $this->result = 'already';

            return;
        }

        AuditLog::record('participant.checked_in', $participant);
        $this->result = 'ok';
        $this->dispatch('wirekit-toast', variant: 'success', title: 'เช็กอินแล้ว', message: $participant->displayName());
    }

    /** Pay-at-venue groups: staff records the money once for the whole group. */
    public function receiveOnsite(): void
    {
        $group = Participant::query()->findOrFail($this->participantId)->group;
        abort_if($group->isPaid(), 422);

        DB::transaction(function () use ($group) {
            $group->forceFill([
                'payment_method' => PaymentMethod::Onsite,
                'payment_status' => PaymentStatus::OnsitePaid,
                'paid_amount' => $group->total_amount,
                'onsite_received_by' => auth()->id(),
                'onsite_received_at' => now(),
                'reject_reason' => null,
            ])->save();
        });

        AuditLog::record('payment.onsite_received', $group, ['amount' => (float) $group->total_amount]);
        $this->dispatch('wirekit-toast', variant: 'success', title: 'บันทึกรับเงินแล้ว', message: number_format($group->total_amount, 2).' บาท');
    }

    public function render()
    {
        $matches = collect();
        $term = trim($this->search);

        if (mb_strlen($term) >= 2) {
            $matches = Participant::query()->with('group.university')
                ->where(fn ($q) => $q->where('full_name', 'like', "%{$term}%")
                    ->orWhere('mobile_phone', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%"))
                ->limit(10)->get();
        }

        $participant = $this->participantId
            ? Participant::query()->with(['group.university', 'group.participants', 'type', 'checkedInBy'])->find($this->participantId)
            : null;

        return view('livewire.staff.scanner', [
            'matches' => $matches,
            'participant' => $participant,
            'stats' => [
                'checked' => Participant::query()->whereNotNull('checked_in_at')->count(),
                'total' => Participant::query()->count(),
            ],
        ]);
    }
}
