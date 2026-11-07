<?php

namespace App\Services;

use App\Enums\Attendance;
use App\Enums\PaymentStatus;
use App\Models\Participant;
use App\Models\ParticipantType;
use App\Models\RegistrationGroup;
use Illuminate\Support\Collection;

class FeeCalculator
{
    /**
     * Per-type breakdown of a group from each participant's snapshotted fee.
     * Board-game-only entrants pay nothing, so they count toward `count` and
     * `boardGame` but not toward the fee lines.
     *
     * @return array{lines: Collection<int, array{type: string, count: int, fee: float, subtotal: float}>, total: float, count: int, boardGame: array<string, int>}
     */
    public static function forGroup(RegistrationGroup $group): array
    {
        $types = ParticipantType::query()->orderBy('sort_order')->get()->keyBy('id');

        $lines = $group->participants()
            ->where('attendance', '!=', Attendance::BoardGame)
            ->selectRaw('participant_type_id, fee_amount, count(*) as people, sum(fee_amount) as subtotal')
            ->groupBy('participant_type_id', 'fee_amount')
            ->get()
            ->sortBy(fn ($row) => $types[$row->participant_type_id]->sort_order ?? 0)
            ->map(fn ($row) => [
                'type' => $types[$row->participant_type_id]->name_th ?? '-',
                'count' => (int) $row->people,
                'fee' => (float) $row->fee_amount,
                'subtotal' => (float) $row->subtotal,
            ])
            ->values();

        return [
            'lines' => $lines,
            'total' => (float) $lines->sum('subtotal'),
            'count' => $group->participants()->count(),
            'boardGame' => self::boardGameTeam($group),
        ];
    }

    /**
     * Board game team members of a group, counted per participant type code.
     *
     * @return array<string, int>
     */
    public static function boardGameTeam(RegistrationGroup $group, ?int $exceptParticipantId = null): array
    {
        $counts = $group->participants()
            ->whereIn('attendance', [Attendance::BoardGame, Attendance::ConferenceAndBoardGame])
            ->when($exceptParticipantId, fn ($q) => $q->whereKeyNot($exceptParticipantId))
            ->join('participant_types', 'participant_types.id', '=', 'participants.participant_type_id')
            ->selectRaw('participant_types.code, count(*) as people')
            ->groupBy('participant_types.code')
            ->pluck('people', 'code');

        return collect(array_keys(Participant::BOARD_GAME_LIMITS))
            ->mapWithKeys(fn (string $code) => [$code => (int) ($counts[$code] ?? 0)])
            ->all();
    }

    /** Current fee for a type, used to snapshot fee_amount when a participant is added or changes type. */
    public static function feeFor(int $participantTypeId): float
    {
        return (float) ParticipantType::query()->whereKey($participantTypeId)->value('fee');
    }

    /**
     * Store the group total. A slip that was waiting for review no longer matches
     * once the amount changes, so the group goes back to "pending".
     */
    public static function refreshTotal(RegistrationGroup $group): void
    {
        $total = (float) $group->participants()->sum('fee_amount');

        if (abs($total - (float) $group->total_amount) < 0.005) {
            return;
        }

        $group->total_amount = $total;

        if (in_array($group->payment_status, [PaymentStatus::Submitted, PaymentStatus::Rejected], true)) {
            $group->payment_status = PaymentStatus::Pending;
            $group->reject_reason = 'ยอดเงินเปลี่ยนหลังแนบสลิป กรุณาตรวจสอบยอดและแนบสลิปใหม่';
        }

        $group->save();
    }

    public static function snapshot(Participant $participant): void
    {
        if (! $participant->exists || $participant->isDirty(['participant_type_id', 'attendance']) || $participant->fee_amount === null) {
            $participant->fee_amount = $participant->attendsConference() ? self::feeFor($participant->participant_type_id) : 0;
        }
    }
}
