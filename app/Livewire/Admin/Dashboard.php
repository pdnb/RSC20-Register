<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Models\Participant;
use App\Models\ParticipantType;
use App\Models\RegistrationGroup;
use App\Models\University;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('ภาพรวม')]
class Dashboard extends Component
{
    public function render()
    {
        $groups = RegistrationGroup::query()->get();
        $paid = $groups->filter->isPaid();
        $participants = Participant::query()->count();
        $checkedIn = Participant::query()->whereNotNull('checked_in_at')->count();
        $expected = (float) $groups->sum('total_amount');
        $paidAmount = (float) $paid->sum('total_amount');
        $byUniversity = University::query()->orderBy('sort_order')
            ->with(['group' => fn ($q) => $q->withCount(['participants', 'participants as checked_in_count' => fn ($p) => $p->whereNotNull('checked_in_at')])])
            ->get();
        $groupCountByStatus = $groups->countBy(fn ($g) => $g->payment_status->value);

        return view('livewire.admin.dashboard', [
            'stats' => [
                'participants' => $participants,
                'universities' => $groups->filter(fn ($g) => $g->total_amount > 0)->count(),
                'expected' => $expected,
                'paid' => $paidAmount,
                'paidPercent' => $expected > 0 ? (int) round($paidAmount * 100 / $expected) : 0,
                'pendingSlips' => $groupCountByStatus[PaymentStatus::Submitted->value] ?? 0,
                'checkedIn' => $checkedIn,
                'checkedInPercent' => $participants > 0 ? (int) round($checkedIn * 100 / $participants) : 0,
                'registeredUniversities' => $byUniversity->filter(fn ($u) => $u->group !== null)->count(),
                'totalUniversities' => $byUniversity->count(),
                'groups' => $groups->count(),
            ],
            'byStatus' => collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $status) => [$status->value => [
                'status' => $status,
                'count' => $groupCountByStatus[$status->value] ?? 0,
            ]]),
            'byType' => ParticipantType::query()->orderBy('sort_order')->withCount('participants')->get(),
            'byShirt' => Participant::query()->selectRaw('shirt_size, count(*) c')->groupBy('shirt_size')->pluck('c', 'shirt_size'),
            'byFood' => Participant::query()->selectRaw('food_type, count(*) c')->groupBy('food_type')->orderByDesc('c')->pluck('c', 'food_type'),
            'allergies' => Participant::query()->whereNotNull('food_allergy')->count(),
            'byUniversity' => $byUniversity,
            'feesMissing' => ParticipantType::query()->where('fee', '<=', 0)->exists(),
        ]);
    }
}
