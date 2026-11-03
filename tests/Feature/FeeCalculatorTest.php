<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\ParticipantType;
use App\Services\FeeCalculator;
use Tests\TestCase;

class FeeCalculatorTest extends TestCase
{
    public function test_total_of_mixed_types(): void
    {
        $group = $this->group();
        $this->participant($group, ParticipantType::EXECUTIVE);
        $this->participant($group, ParticipantType::STAFF);
        $this->participant($group, ParticipantType::STUDENT);
        $this->participant($group, ParticipantType::STUDENT);

        $summary = FeeCalculator::forGroup($group);

        $this->assertSame(7500.0, $summary['total']);
        $this->assertSame(4, $summary['count']);
        $this->assertSame(['ผู้บริหาร', 'บุคลากร', 'นักศึกษา'], $summary['lines']->pluck('type')->all());
        $this->assertSame(3000.0, $summary['lines']->firstWhere('type', 'นักศึกษา')['subtotal']);
        $this->assertSame('7500.00', $group->fresh()->total_amount);
    }

    public function test_fee_is_snapshotted_when_registering(): void
    {
        $group = $this->group();
        $p = $this->participant($group, ParticipantType::STUDENT);

        ParticipantType::query()->where('code', ParticipantType::STUDENT)->update(['fee' => 9999]);
        $p->update(['full_name' => 'เปลี่ยนชื่อ']);

        $this->assertSame('1500.00', $p->fresh()->fee_amount);
        $this->assertSame(1500.0, FeeCalculator::forGroup($group)['total']);

        $this->participant($group, ParticipantType::STUDENT);
        $this->assertSame('11499.00', $group->fresh()->total_amount);
    }

    public function test_total_updates_on_delete_and_type_change(): void
    {
        $group = $this->group();
        $a = $this->participant($group, ParticipantType::STUDENT);
        $b = $this->participant($group, ParticipantType::STUDENT);
        $this->assertSame('3000.00', $group->fresh()->total_amount);

        $a->update(['participant_type_id' => ParticipantType::byCode(ParticipantType::STAFF)->id, 'position' => 'บุคลากร']);
        $this->assertSame('3500.00', $group->fresh()->total_amount);

        $b->delete();
        $this->assertSame('2000.00', $group->fresh()->total_amount);
    }

    public function test_submitted_slip_goes_back_to_pending_when_amount_changes(): void
    {
        $group = $this->group();
        $this->participant($group);
        $group->refresh()->forceFill(['payment_status' => PaymentStatus::Submitted, 'slip_path' => 'slips/x.jpg'])->save();

        $this->participant($group->fresh());

        $group->refresh();
        $this->assertSame(PaymentStatus::Pending, $group->payment_status);
        $this->assertNotNull($group->reject_reason);
    }
}
