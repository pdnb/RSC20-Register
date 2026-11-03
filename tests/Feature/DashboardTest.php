<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Livewire\Admin\Dashboard;
use App\Models\ParticipantType;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_summarises_payment_and_check_in_progress(): void
    {
        $paidGroup = $this->group($this->university('มหาวิทยาลัยราชภัฏภูเก็ต'));
        $this->participant($paidGroup, ParticipantType::EXECUTIVE, ['checked_in_at' => now()]);
        $paidGroup->refresh()->forceFill(['payment_status' => PaymentStatus::Approved])->save();

        $pendingGroup = $this->group($this->university('มหาวิทยาลัยราชภัฏยะลา'));
        $this->participant($pendingGroup);
        $this->participant($pendingGroup);
        $pendingGroup->refresh();

        $paid = (float) $paidGroup->total_amount;
        $expected = $paid + (float) $pendingGroup->total_amount;

        $component = Livewire::actingAs($this->admin())->test(Dashboard::class)
            ->assertSee(PaymentStatus::Approved->label())
            ->assertSee(PaymentStatus::Pending->label());

        $stats = $component->viewData('stats');
        $this->assertSame((int) round($paid * 100 / $expected), $stats['paidPercent']);
        $this->assertSame(33, $stats['checkedInPercent']);
        $this->assertSame(2, $stats['registeredUniversities']);

        $byStatus = $component->viewData('byStatus');
        $this->assertSame(1, $byStatus[PaymentStatus::Approved->value]['count']);
        $this->assertSame(1, $byStatus[PaymentStatus::Pending->value]['count']);
        $this->assertSame(0, $byStatus[PaymentStatus::Submitted->value]['count']);
    }

    public function test_dashboard_renders_without_any_registrations(): void
    {
        $stats = Livewire::actingAs($this->admin())->test(Dashboard::class)
            ->assertOk()
            ->viewData('stats');

        $this->assertSame(0, $stats['paidPercent']);
        $this->assertSame(0, $stats['checkedInPercent']);
    }
}
