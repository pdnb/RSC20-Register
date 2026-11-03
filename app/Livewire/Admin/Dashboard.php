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

        return view('livewire.admin.dashboard', [
            'stats' => [
                'participants' => Participant::query()->count(),
                'universities' => $groups->filter(fn ($g) => $g->total_amount > 0)->count(),
                'expected' => (float) $groups->sum('total_amount'),
                'paid' => (float) $paid->sum('total_amount'),
                'pendingSlips' => $groups->where('payment_status', PaymentStatus::Submitted)->count(),
                'checkedIn' => Participant::query()->whereNotNull('checked_in_at')->count(),
            ],
            'byType' => ParticipantType::query()->orderBy('sort_order')->withCount('participants')->get(),
            'byShirt' => Participant::query()->selectRaw('shirt_size, count(*) c')->groupBy('shirt_size')->pluck('c', 'shirt_size'),
            'byFood' => Participant::query()->selectRaw('food_type, count(*) c')->groupBy('food_type')->orderByDesc('c')->pluck('c', 'food_type'),
            'allergies' => Participant::query()->whereNotNull('food_allergy')->count(),
            'byUniversity' => University::query()->orderBy('sort_order')
                ->with(['group' => fn ($q) => $q->withCount(['participants', 'participants as checked_in_count' => fn ($p) => $p->whereNotNull('checked_in_at')])])
                ->get(),
            'feesMissing' => ParticipantType::query()->where('fee', '<=', 0)->exists(),
        ]);
    }
}
