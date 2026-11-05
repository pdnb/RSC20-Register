<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Livewire\Admin\Groups;
use App\Livewire\Coordinator\GroupManager;
use App\Livewire\Coordinator\Payment;
use App\Livewire\Staff\Scanner;
use App\Models\Participant;
use App\Models\ParticipantType;
use App\Models\RegistrationGroup;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_coordinator_adds_executive_and_student_with_photo(): void
    {
        $user = $this->coordinator();
        $id = $this->nextNationalId();

        Livewire::actingAs($user)->test(GroupManager::class)
            ->call('create', 'executive')
            ->set('form.position', 'รองอธิการบดีฝ่ายกิจการนักศึกษา')
            ->set('form.title', 'ผศ.ดร.')
            ->set('form.full_name', 'สมชาย ใจดี')
            ->set('form.mobile_phone', '081-234-5678')
            ->set('form.national_id', $id)
            ->set('form.food_type', 'อาหารฮาลาล')
            ->set('form.food_allergy', 'กุ้ง')
            ->set('form.shirt_size', 'M-XL')
            ->set('form.use_group_receipt', false)
            ->set('form.receipt_name', 'ผศ.ดร.สมชาย ใจดี')
            ->set('form.receipt_tax_id', $id)
            ->set('form.receipt_address', 'สุราษฎร์ธานี')
            ->set('photo', UploadedFile::fake()->image('me.png', 800, 1000))
            ->set('pdpa', true)
            ->call('save')
            ->assertHasNoErrors();

        $p = Participant::query()->firstOrFail();
        $this->assertSame('executive', $p->type->code);
        $this->assertSame('2500.00', $p->fee_amount);
        $this->assertSame('0812345678', $p->mobile_phone);
        $this->assertSame('ผศ.ดร.สมชาย ใจดี', $p->receipt_name);
        $this->assertSame($id, $p->receipt_tax_id);
        $this->assertSame('RSC20-0001', $p->code);
        Storage::disk('local')->assertExists($p->badge_photo_path);
        // national ID is encrypted at rest
        $this->assertNotSame($id, $p->getRawOriginal('national_id'));

        Livewire::actingAs($user)->test(GroupManager::class)
            ->call('create', 'member')
            ->set('form.position', 'นักศึกษา')
            ->set('form.full_name', 'สมหญิง เรียนดี')
            ->set('form.title', 'นางสาว')
            ->set('form.mobile_phone', '0899999999')
            ->set('form.national_id', $this->nextNationalId())
            ->set('form.shirt_size', 'F-S')
            ->set('form.use_group_receipt', false)
            ->set('form.receipt_name', 'องค์การนักศึกษา')
            ->set('form.receipt_tax_id', $this->nextNationalId())
            ->set('form.receipt_address', 'สุราษฎร์ธานี')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('4000.00', RegistrationGroup::query()->first()->total_amount);
    }

    public function test_badge_photo_accepts_up_to_5mb(): void
    {
        $component = Livewire::actingAs($this->coordinator())->test(GroupManager::class)->call('create', 'member');

        $component->set('photo', UploadedFile::fake()->image('ok.jpg')->size(5120))
            ->call('save')
            ->assertHasNoErrors('photo');

        $component->set('photo', UploadedFile::fake()->image('big.jpg')->size(5121))
            ->call('save')
            ->assertHasErrors(['photo' => 'max']);
    }

    public function test_participants_inherit_shared_receipt_info_and_follow_its_changes(): void
    {
        $user = $this->coordinator();
        $taxId = $this->nextNationalId();

        $component = Livewire::actingAs($user)->test(GroupManager::class)
            ->set('receipt.name', 'มหาวิทยาลัยราชภัฏสุราษฎร์ธานี')
            ->set('receipt.address', '272 ถ.สุราษฎร์-นาสาร')
            ->set('receipt.tax_id', '1234567890123')
            ->call('saveReceipt')
            ->assertHasErrors(['receipt.tax_id'])
            ->assertSee('เลขประจำตัวผู้เสียภาษีไม่ถูกต้อง')
            ->set('receipt.tax_id', $taxId)
            ->call('saveReceipt')
            ->assertHasNoErrors()
            ->call('create', 'member')
            ->set('form.full_name', 'สมหญิง เรียนดี')
            ->set('form.mobile_phone', '0899999999')
            ->set('form.national_id', $this->nextNationalId())
            ->set('form.shirt_size', 'F-S')
            ->set('pdpa', true)
            ->call('save')
            ->assertHasNoErrors();

        $p = Participant::query()->firstOrFail();
        $this->assertNull($p->receipt_name);
        $this->assertNull($p->receipt_tax_id);
        $this->assertSame('มหาวิทยาลัยราชภัฏสุราษฎร์ธานี', $p->receiptName());
        $this->assertSame('272 ถ.สุราษฎร์-นาสาร', $p->receiptAddress());
        $this->assertSame($taxId, $p->receiptTaxId());

        $component->set('receipt.name', 'สำนักงานอธิการบดี')->call('saveReceipt')->assertHasNoErrors();

        $this->assertSame('สำนักงานอธิการบดี', $p->fresh()->receiptName());
    }

    public function test_participant_own_receipt_info_overrides_shared_info(): void
    {
        $ownTaxId = $this->nextNationalId();

        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->set('receipt.name', 'มหาวิทยาลัยราชภัฏสุราษฎร์ธานี')
            ->set('receipt.address', 'สุราษฎร์ธานี')
            ->set('receipt.tax_id', $this->nextNationalId())
            ->call('saveReceipt')
            ->call('create', 'member')
            ->set('form.full_name', 'สมหญิง เรียนดี')
            ->set('form.mobile_phone', '0899999999')
            ->set('form.national_id', $this->nextNationalId())
            ->set('form.shirt_size', 'F-S')
            ->set('form.use_group_receipt', false)
            ->set('form.receipt_name', 'องค์การนักศึกษา')
            ->set('form.receipt_tax_id', $ownTaxId)
            ->set('form.receipt_address', 'อาคารกิจการนักศึกษา')
            ->set('pdpa', true)
            ->call('save')
            ->assertHasNoErrors();

        $p = Participant::query()->firstOrFail();
        $this->assertFalse($p->usesGroupReceipt());
        $this->assertSame('องค์การนักศึกษา', $p->receiptName());
        $this->assertSame('อาคารกิจการนักศึกษา', $p->receiptAddress());
        $this->assertSame($ownTaxId, $p->receiptTaxId());
    }

    public function test_using_shared_receipt_info_fails_when_group_has_none(): void
    {
        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('create', 'member')
            ->set('form.full_name', 'สมหญิง เรียนดี')
            ->set('form.mobile_phone', '0899999999')
            ->set('form.national_id', $this->nextNationalId())
            ->set('form.shirt_size', 'F-S')
            ->set('pdpa', true)
            ->call('save')
            ->assertHasErrors(['form.use_group_receipt'])
            ->assertSee('กรุณาบันทึกข้อมูลใบเสร็จกลางของมหาวิทยาลัยก่อน');

        $this->assertSame(0, Participant::query()->count());
    }

    public function test_validation_rejects_bad_and_duplicate_ids(): void
    {
        $other = $this->group($this->university('มหาวิทยาลัยราชภัฏภูเก็ต'));
        $taken = $this->participant($other);

        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('create', 'member')
            ->set('form.full_name', 'x')
            ->set('form.mobile_phone', '12345')
            ->set('form.national_id', '1234567890123')
            ->set('form.use_group_receipt', false)
            ->set('form.receipt_address', 'x')
            ->set('pdpa', true)
            ->call('save')
            ->assertHasErrors(['form.mobile_phone', 'form.national_id', 'form.receipt_name', 'form.receipt_tax_id'])
            ->set('form.mobile_phone', '0812345678')
            ->set('form.national_id', $taken->national_id)
            ->call('save')
            ->assertHasErrors(['form.national_id']);

        $this->assertSame(1, Participant::query()->count());
    }

    public function test_pdpa_consent_required_before_first_save(): void
    {
        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('create', 'member')
            ->set('form.full_name', 'ก ข')
            ->set('form.mobile_phone', '0812345678')
            ->set('form.national_id', $this->nextNationalId())
            ->set('form.receipt_address', 'x')
            ->call('save')
            ->assertHasErrors('pdpa');
    }

    public function test_wizard_next_step_validates_only_the_current_step(): void
    {
        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('create', 'member')
            ->call('nextStep')
            ->assertHasErrors(['form.full_name', 'form.mobile_phone', 'form.national_id'])
            ->assertHasNoErrors(['form.shirt_size', 'pdpa'])
            ->assertSet('step', 1)
            ->set('form.full_name', 'สมหญิง เรียนดี')
            ->set('form.mobile_phone', '089-999-9999')
            ->set('form.national_id', $this->nextNationalId())
            ->call('nextStep')
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->call('nextStep')
            ->assertHasErrors(['form.shirt_size'])
            ->assertSet('step', 2)
            ->call('goToStep', 1)
            ->assertSet('step', 1)
            ->call('goToStep', 3)
            ->assertSet('step', 1);
    }

    public function test_save_returns_to_the_first_step_with_errors(): void
    {
        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('create', 'member')
            ->set('step', 3)
            ->set('form.shirt_size', 'F-S')
            ->set('pdpa', true)
            ->call('save')
            ->assertHasErrors(['form.full_name'])
            ->assertSet('step', 1);
    }

    public function test_receipt_can_be_filled_from_participant(): void
    {
        $id = $this->nextNationalId();

        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('create', 'executive')
            ->set('form.title', 'อื่นๆ')
            ->set('form.title_other', 'พล.ต.ท.')
            ->set('form.full_name', 'สมชาย ใจดี')
            ->set('form.national_id', $id)
            ->call('fillReceiptFromParticipant')
            ->assertSet('form.use_group_receipt', false)
            ->assertSet('form.receipt_name', 'พล.ต.ท.สมชาย ใจดี')
            ->assertSet('form.receipt_tax_id', $id);
    }

    public function test_save_and_add_another_keeps_form_open_for_same_section(): void
    {
        Livewire::actingAs($this->coordinator())->test(GroupManager::class)
            ->call('create', 'member')
            ->set('form.position', 'บุคลากร')
            ->set('form.full_name', 'สมหญิง เรียนดี')
            ->set('form.mobile_phone', '0899999999')
            ->set('form.national_id', $this->nextNationalId())
            ->set('form.shirt_size', 'F-S')
            ->set('form.use_group_receipt', false)
            ->set('form.receipt_name', 'สมหญิง เรียนดี')
            ->set('form.receipt_tax_id', $this->nextNationalId())
            ->set('form.receipt_address', 'สุราษฎร์ธานี')
            ->set('pdpa', true)
            ->set('step', 3)
            ->call('save', true)
            ->assertHasNoErrors()
            ->assertNotDispatched('wirekit-modal-close')
            ->assertSet('step', 1)
            ->assertSet('editingId', null)
            ->assertSet('form.section', 'member')
            ->assertSet('form.full_name', '');

        $this->assertSame(1, Participant::query()->count());
    }

    public function test_coordinator_only_sees_own_university_files(): void
    {
        $mine = $this->coordinator();
        $theirs = $this->group($this->university('มหาวิทยาลัยราชภัฏยะลา'));
        $theirs->forceFill(['slip_path' => 'slips/x.jpg'])->save();
        Storage::disk('local')->put('slips/x.jpg', 'x');
        $p = $this->participant($theirs);

        $this->actingAs($mine)->get(route('files.slip', $theirs))->assertForbidden();
        $this->actingAs($mine)->get(route('files.badge', $p))->assertForbidden();
        $this->actingAs($mine)->get(route('admin.groups.show', $theirs))->assertForbidden();
    }

    public function test_editing_locked_after_payment_or_after_close(): void
    {
        $user = $this->coordinator();
        $group = $this->group();
        $this->participant($group);
        $group->refresh()->forceFill(['payment_status' => PaymentStatus::Approved])->save();

        Livewire::actingAs($user)->test(GroupManager::class)->call('create', 'member')->assertForbidden();
        Livewire::actingAs($user)->test(GroupManager::class)->call('saveReceipt')->assertForbidden();

        $group->forceFill(['payment_status' => PaymentStatus::Pending])->save();
        Setting::put(['registration_closes_at' => now()->subDay()->toDateTimeString()]);
        Livewire::actingAs($user)->test(GroupManager::class)->call('create', 'member')->assertForbidden();
    }

    public function test_admin_unlock_lets_coordinator_edit_existing_participants_but_not_add_or_delete(): void
    {
        $user = $this->coordinator();
        $group = $this->group();
        $p = $this->participant($group, attrs: ['badge_photo_path' => 'badges/x.jpg']);
        $group->refresh()->forceFill(['payment_status' => PaymentStatus::Approved])->save();

        Livewire::actingAs($user)->test(GroupManager::class)->call('edit', $p->id)->assertForbidden();

        Livewire::actingAs($this->admin())->test(GroupManager::class, ['group' => $group])->call('toggleEditUnlock');
        $this->assertTrue($group->refresh()->isEditUnlocked());

        Livewire::actingAs($user)->test(GroupManager::class)
            ->call('edit', $p->id)
            ->set('form.full_name', 'ชื่อ ใหม่')
            ->set('pdpa', true)
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('ชื่อ ใหม่', $p->refresh()->full_name);

        Livewire::actingAs($user)->test(GroupManager::class)
            ->call('edit', $p->id)
            ->set('form.position', 'บุคลากร')
            ->set('pdpa', true)
            ->call('save')
            ->assertHasErrors('form.position');
        $this->assertSame(ParticipantType::STUDENT, $p->refresh()->type->code);

        Livewire::actingAs($user)->test(GroupManager::class)->call('create', 'member')->assertForbidden();
        Livewire::actingAs($user)->test(GroupManager::class)->call('delete', $p->id)->assertForbidden();
        Livewire::actingAs($user)->test(GroupManager::class)->call('saveReceipt')->assertForbidden();

        Livewire::actingAs($this->admin())->test(GroupManager::class, ['group' => $group])->call('toggleEditUnlock');
        Livewire::actingAs($user)->test(GroupManager::class)->call('edit', $p->id)->assertForbidden();
    }

    public function test_coordinator_cannot_toggle_edit_unlock(): void
    {
        $this->group();

        Livewire::actingAs($this->coordinator())->test(GroupManager::class)->call('toggleEditUnlock')->assertForbidden();
    }

    public function test_slip_submit_then_admin_approves(): void
    {
        $user = $this->coordinator();
        $group = $this->group();
        $this->participant($group, ParticipantType::STAFF);

        Livewire::actingAs($user)->test(Payment::class)
            ->set('slip', UploadedFile::fake()->image('slip.jpg', 600, 1200))
            ->set('transferred_at', now()->subHour()->format('Y-m-d\TH:i'))
            ->set('slip_reference', 'REF123')
            ->call('submitSlip')
            ->assertHasNoErrors();

        $group->refresh();
        $this->assertSame(PaymentStatus::Submitted, $group->payment_status);
        Storage::disk('local')->assertExists($group->slip_path);

        $admin = $this->admin();
        Livewire::actingAs($admin)->test(Groups::class)->call('review', $group->id)->call('approve');

        $group->refresh();
        $this->assertSame(PaymentStatus::Approved, $group->payment_status);
        $this->assertSame('2000.00', $group->paid_amount);
        $this->assertSame($admin->id, $group->approved_by);
    }

    public function test_transferred_at_is_validated_against_bangkok_time(): void
    {
        $this->travelTo(Carbon::parse('2026-11-03 03:00:00', 'UTC'));
        $user = $this->coordinator();
        $group = $this->group();
        $this->participant($group);

        $submit = fn (string $transferredAt) => Livewire::actingAs($user)->test(Payment::class)
            ->set('slip', UploadedFile::fake()->image('slip.jpg', 600, 1200))
            ->set('transferred_at', $transferredAt)
            ->call('submitSlip');

        $submit('2026-11-03T09:30')->assertHasNoErrors();
        $submit('2026-11-03T10:30')->assertHasErrors(['transferred_at' => 'before_or_equal']);
        $submit('03/11/2026 09:30')->assertHasErrors(['transferred_at' => 'date_format']);
    }

    public function test_admin_rejects_slip_with_reason(): void
    {
        $group = $this->group();
        $this->participant($group);
        $group->refresh()->forceFill(['payment_status' => PaymentStatus::Submitted, 'slip_path' => 's.jpg'])->save();

        Livewire::actingAs($this->admin())->test(Groups::class)
            ->call('review', $group->id)->call('reject')->assertHasErrors('rejectReason')
            ->set('rejectReason', 'ยอดไม่ตรง')->call('reject')->assertHasNoErrors();

        $this->assertSame(PaymentStatus::Rejected, $group->fresh()->payment_status);
    }

    public function test_checkin_by_qr_once_and_onsite_payment(): void
    {
        $group = $this->group();
        $p = $this->participant($group);
        $staff = $this->staff();

        Livewire::actingAs($staff)->test(Scanner::class)
            ->call('scanned', route('ticket', $p->qr_token))
            ->assertSet('participantId', $p->id)
            ->call('receiveOnsite')
            ->call('checkIn')
            ->assertSet('result', 'ok')
            ->call('checkIn')
            ->assertSet('result', 'already');

        $p->refresh();
        $this->assertNotNull($p->checked_in_at);
        $this->assertSame($staff->id, $p->checked_in_by);
        $this->assertSame(PaymentStatus::OnsitePaid, $group->fresh()->payment_status);

        Livewire::actingAs($staff)->test(Scanner::class)->call('scanned', 'nonsense')->assertSet('result', 'notfound');
    }

    public function test_public_ticket_hides_personal_data(): void
    {
        $p = $this->participant($this->group());

        $this->get(route('ticket', $p->qr_token))->assertOk()
            ->assertSee($p->full_name)
            ->assertDontSee($p->national_id)
            ->assertDontSee($p->mobile_phone);
    }

    public function test_pages_render(): void
    {
        $coordinator = $this->coordinator();
        $group = $this->group();
        $this->participant($group);
        $admin = $this->admin();

        foreach (['coordinator.dashboard', 'coordinator.payment', 'coordinator.password'] as $route) {
            $this->actingAs($coordinator)->get(route($route))->assertOk();
        }
        $this->actingAs($coordinator)->get('/qr')->assertNotFound();

        foreach (['admin.dashboard', 'admin.groups', 'admin.participants', 'admin.coordinators', 'admin.users', 'admin.settings', 'admin.exports', 'admin.finance', 'staff.checkin'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.groups.show', $group))->assertOk();

        auth()->logout();
        $this->get(route('login'))->assertOk();
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_new_participants_follow_the_admin_set_code_format_and_existing_codes_stay(): void
    {
        $group = $this->group();
        $existing = $this->participant($group);
        $this->assertSame('RSC20-0001', $existing->code);

        Setting::put(['participant_code_prefix' => 'SRU-', 'participant_code_digits' => '3', 'participant_code_start' => '50']);

        $this->assertSame('SRU-050', $this->participant($group)->code);
        $this->assertSame('SRU-051', $this->participant($group)->code);
        $this->assertSame('RSC20-0001', $existing->fresh()->code);

        // Back on the old prefix, numbering continues past the highest code already issued under it.
        Setting::put(['participant_code_prefix' => 'RSC20-', 'participant_code_digits' => '4', 'participant_code_start' => '1']);
        $this->assertSame('RSC20-0002', $this->participant($group)->code);
    }
}
