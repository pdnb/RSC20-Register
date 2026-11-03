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
            ->set('form.shirt_size', 'XL')
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
        $this->assertSame('RSC20-'.str_pad((string) $p->id, 4, '0', STR_PAD_LEFT), $p->code);
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
            ->set('form.receipt_same', false)
            ->set('form.receipt_name', 'องค์การนักศึกษา')
            ->set('form.receipt_tax_id', $this->nextNationalId())
            ->set('form.receipt_address', 'สุราษฎร์ธานี')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('4000.00', RegistrationGroup::query()->first()->total_amount);
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
            ->set('form.receipt_address', 'x')
            ->set('pdpa', true)
            ->call('save')
            ->assertHasErrors(['form.mobile_phone', 'form.national_id'])
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

        $group->forceFill(['payment_status' => PaymentStatus::Pending])->save();
        Setting::put(['registration_closes_at' => now()->subDay()->toDateTimeString()]);
        Livewire::actingAs($user)->test(GroupManager::class)->call('create', 'member')->assertForbidden();
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

        foreach (['coordinator.dashboard', 'coordinator.payment', 'coordinator.qr', 'coordinator.password'] as $route) {
            $this->actingAs($coordinator)->get(route($route))->assertOk();
        }

        foreach (['admin.dashboard', 'admin.groups', 'admin.participants', 'admin.coordinators', 'admin.users', 'admin.settings', 'admin.exports', 'admin.finance', 'staff.checkin'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.groups.show', $group))->assertOk();

        auth()->logout();
        $this->get(route('login'))->assertOk();
        $this->get(route('admin.login'))->assertOk();
    }
}
