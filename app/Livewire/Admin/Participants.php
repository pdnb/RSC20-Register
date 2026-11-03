<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\ParticipantType;
use App\Models\University;
use App\Support\ThaiId;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('ผู้เข้าร่วม')]
class Participants extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    /** @var array<int, string> university ids; empty means all */
    #[Url]
    public array $university = [];

    /** @var array<int, string> participant type ids; empty means all */
    #[Url]
    public array $type = [];

    /** @var array<int, string> participant id → revealed ID number */
    public array $revealed = [];

    public function updating($name): void
    {
        if (in_array($name, ['search', 'university', 'type'], true)) {
            $this->resetPage();
        }
    }

    public function reveal(int $id): void
    {
        $p = Participant::query()->findOrFail($id);
        AuditLog::record('participant.national_id_viewed', $p);
        $this->revealed[$id] = ThaiId::format($p->national_id);
    }

    public function render()
    {
        $term = trim($this->search);

        return view('livewire.admin.participants', [
            'participants' => Participant::query()->with(['group.university', 'type'])
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('full_name', 'like', "%{$term}%")
                    ->orWhere('mobile_phone', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")))
                ->when($this->university !== [], fn ($q) => $q->whereHas('group', fn ($g) => $g->whereIn('university_id', $this->university)))
                ->when($this->type !== [], fn ($q) => $q->whereIn('participant_type_id', $this->type))
                ->orderBy('id')->paginate(25),
            'universities' => University::query()->orderBy('sort_order')->pluck('name', 'id'),
            'types' => ParticipantType::query()->orderBy('sort_order')->pluck('name_th', 'id'),
        ]);
    }
}
