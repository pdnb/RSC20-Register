<?php

namespace App\Livewire\Coordinator;

use App\Livewire\Coordinator\Concerns\ResolvesGroup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('QR เช็กอิน')]
class QrCards extends Component
{
    use ResolvesGroup;

    public function mount(): void
    {
        $this->resolveGroup();
    }

    public function render()
    {
        return view('livewire.coordinator.qr-cards', [
            'participants' => $this->group->participants()->with('type')->orderBy('section')->orderBy('id')->get(),
        ]);
    }
}
