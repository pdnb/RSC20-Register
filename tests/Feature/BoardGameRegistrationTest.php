<?php

namespace Tests\Feature;

use App\Enums\Attendance;
use App\Enums\PaymentStatus;
use App\Livewire\Coordinator\GroupManager;
use App\Models\Participant;
use App\Models\ParticipantType;
use App\Services\FeeCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class BoardGameRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_board_game_only_member_needs_no_shirt_or_receipt_and_pays_nothing(): void
    {
        $group = $this->group();
        $this->participant($group, ParticipantType::STUDENT);

        $this->memberForm('นักศึกษา', Attendance::BoardGame)
            ->call('save')
            ->assertHasNoErrors();

        $entrant = Participant::query()->where('attendance', Attendance::BoardGame)->firstOrFail();
        $this->assertNull($entrant->shirt_size);
        $this->assertNull($entrant->receipt_name);
        $this->assertSame('0.00', $entrant->fee_amount);
        $this->assertNotNull($entrant->badge_photo_path);
        $this->assertSame('1500.00', $group->refresh()->total_amount);

        $summary = FeeCalculator::forGroup($group);
        $this->assertSame(2, $summary['count']);
        $this->assertSame(1500.0, $summary['total']);
        $this->assertSame(1, $summary['lines']->sum('count'));
    }

    public function test_board_game_only_member_can_choose_food_and_note_an_allergy(): void
    {
        $this->memberForm('นักศึกษา', Attendance::BoardGame)
            ->set('form.food_type', 'อื่นๆ')
            ->set('form.food_type_other', '')
            ->call('save')
            ->assertHasErrors('form.food_type_other');

        $this->memberForm('นักศึกษา', Attendance::BoardGame)
            ->set('form.food_type', 'อาหารฮาลาล')
            ->set('form.food_allergy', 'กุ้ง')
            ->call('save')
            ->assertHasNoErrors();

        $entrant = Participant::query()->firstOrFail();
        $this->assertSame('อาหารฮาลาล', $entrant->food_type);
        $this->assertSame('กุ้ง', $entrant->food_allergy);
        $this->assertNull($entrant->shirt_size);
        $this->assertSame('0.00', $entrant->fee_amount);
    }

    public function test_conference_and_board_game_still_needs_food_and_shirt_and_pays_the_fee(): void
    {
        $this->memberForm('นักศึกษา', Attendance::ConferenceAndBoardGame)
            ->call('save')
            ->assertHasErrors(['form.shirt_size', 'form.use_group_receipt']);

        $this->memberForm('นักศึกษา', Attendance::ConferenceAndBoardGame)
            ->set('form.shirt_size', 'F-S')
            ->set('form.use_group_receipt', false)
            ->set('form.receipt_name', 'องค์การนักศึกษา')
            ->set('form.receipt_tax_id', $this->nextNationalId())
            ->set('form.receipt_address', 'สุราษฎร์ธานี')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('1500.00', Participant::query()->firstOrFail()->fee_amount);
    }

    public function test_board_game_team_is_limited_to_one_staff_and_six_students_per_university(): void
    {
        $group = $this->group();
        foreach (range(1, 3) as $i) {
            $this->participant($group, ParticipantType::STUDENT, ['attendance' => Attendance::BoardGame]);
            $this->participant($group, ParticipantType::STUDENT, ['attendance' => Attendance::ConferenceAndBoardGame]);
        }
        $this->participant($group, ParticipantType::STAFF, ['attendance' => Attendance::ConferenceAndBoardGame]);
        // Another university's team does not count toward this one.
        $this->participant($this->group($this->university('มหาวิทยาลัยราชภัฏภูเก็ต')), ParticipantType::STAFF, ['attendance' => Attendance::BoardGame]);

        $this->memberForm('นักศึกษา', Attendance::BoardGame)->call('save')->assertHasErrors('form.attendance');
        $this->memberForm('นักศึกษา', Attendance::ConferenceAndBoardGame)->call('save')->assertHasErrors('form.attendance');
        $this->memberForm('บุคลากร', Attendance::BoardGame)->call('save')->assertHasErrors('form.attendance');
        $this->memberForm('นักศึกษา', Attendance::Conference)->call('save')->assertHasNoErrors('form.attendance');
    }

    public function test_editing_a_board_game_member_does_not_count_them_against_the_limit(): void
    {
        $group = $this->group();
        $staff = $this->participant($group, ParticipantType::STAFF, [
            'attendance' => Attendance::BoardGame, 'badge_photo_path' => 'badges/x.jpg',
        ]);

        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('edit', $staff->id)
            ->set('form.full_name', 'ชื่อ ใหม่')
            ->set('pdpa', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ชื่อ ใหม่', $staff->refresh()->full_name);
    }

    public function test_board_game_seats_left_counts_down_and_excludes_the_participant_being_edited(): void
    {
        $group = $this->group();
        $staff = $this->participant($group, ParticipantType::STAFF, ['attendance' => Attendance::BoardGame]);

        $component = Livewire::actingAs($this->coordinator())->test(GroupManager::class)->call('create', 'member');
        $this->assertSame([ParticipantType::STAFF => 0, ParticipantType::STUDENT => 6], $component->instance()->boardGameSeatsLeft);

        $component->call('edit', $staff->id);
        $this->assertSame([ParticipantType::STAFF => 1, ParticipantType::STUDENT => 6], $component->instance()->boardGameSeatsLeft);
    }

    public function test_choosing_board_game_moves_off_a_position_whose_team_places_are_taken(): void
    {
        $this->participant($this->group(), ParticipantType::STAFF, ['attendance' => Attendance::BoardGame]);

        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('create', 'member')
            ->assertSet('form.position', 'บุคลากร')
            ->set('form.attendance', Attendance::Conference->value)
            ->assertSet('form.position', 'บุคลากร')
            ->set('form.attendance', Attendance::BoardGame->value)
            ->assertSet('form.position', 'นักศึกษา');
    }

    public function test_changing_attendance_resnapshots_the_fee_and_group_total(): void
    {
        $group = $this->group();
        $student = $this->participant($group, ParticipantType::STUDENT);
        $this->assertSame('1500.00', $group->refresh()->total_amount);

        $student->update(['attendance' => Attendance::BoardGame]);
        $this->assertSame('0.00', $student->fee_amount);
        $this->assertSame('0.00', $group->refresh()->total_amount);

        $student->update(['attendance' => Attendance::ConferenceAndBoardGame]);
        $this->assertSame('1500.00', $group->refresh()->total_amount);
    }

    public function test_edit_unlock_cannot_move_a_participant_into_or_out_of_board_game_only(): void
    {
        $group = $this->group();
        $student = $this->participant($group, ParticipantType::STUDENT, ['badge_photo_path' => 'badges/x.jpg']);
        $group->refresh()->forceFill(['payment_status' => PaymentStatus::Approved, 'edit_unlocked_at' => now()])->save();

        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('edit', $student->id)
            ->set('form.attendance', Attendance::BoardGame->value)
            ->set('pdpa', true)
            ->call('save')
            ->assertHasErrors('form.attendance');
        $this->assertSame(Attendance::Conference, $student->refresh()->attendance);

        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('edit', $student->id)
            ->set('form.attendance', Attendance::ConferenceAndBoardGame->value)
            ->set('pdpa', true)
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(Attendance::ConferenceAndBoardGame, $student->refresh()->attendance);
    }

    /** A filled-in member form, short of food, shirt and receipt details. */
    private function memberForm(string $position, Attendance $attendance): Testable
    {
        return Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('create', 'member')
            ->set('form.position', $position)
            ->set('form.attendance', $attendance->value)
            ->set('form.full_name', 'ผู้แข่งขัน บอร์ดเกม')
            ->set('form.mobile_phone', '0899999999')
            ->set('form.national_id', $this->nextNationalId())
            ->set('photo', UploadedFile::fake()->image('me.png', 400, 400))
            ->set('pdpa', true);
    }
}
