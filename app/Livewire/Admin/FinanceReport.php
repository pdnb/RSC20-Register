<?php

namespace App\Livewire\Admin;

use App\Models\FinanceSubmission;
use App\Models\University;
use App\Services\FinanceReport as Report;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('รายงานนำส่งกองคลังเพื่อออกใบเสร็จ')]
class FinanceReport extends Component
{
    public array $filters = [
        'date_from' => '',
        'date_to' => '',
        'university_id' => '',
        'method' => '',
        'only_new' => true,
    ];

    public function create(): void
    {
        $this->validate([
            'filters.date_from' => ['nullable', 'date'],
            'filters.date_to' => ['nullable', 'date', 'after_or_equal:filters.date_from'],
            'filters.university_id' => ['nullable', 'exists:universities,id'],
            'filters.method' => ['nullable', 'in:transfer,onsite'],
        ], attributes: ['filters.date_to' => 'ถึงวันที่']);

        // Creating a submission always takes only participants never submitted before.
        $submission = Report::create([...$this->filters, 'only_new' => true], auth()->user());

        $this->dispatch('wirekit-toast', variant: 'success', title: 'สร้างรายงานแล้ว',
            message: $submission->submission_no.' · '.$submission->item_count.' รายการ');
    }

    public function render()
    {
        $preview = Report::query($this->filters)->with('group.university');

        return view('livewire.admin.finance-report', [
            'previewCount' => (clone $preview)->count(),
            'previewTotal' => (float) (clone $preview)->sum('fee_amount'),
            'newCount' => Report::query([...$this->filters, 'only_new' => true])->count(),
            'submissions' => FinanceSubmission::query()->with('creator')->latest()->get(),
            'universities' => University::query()->orderBy('sort_order')->pluck('name', 'id'),
        ]);
    }
}
