<?php

namespace Tests\Feature;

use App\Enums\Attendance;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exports\Workbook;
use App\Http\Controllers\Admin\ExportController;
use App\Livewire\Admin\CoordinatorAccounts;
use App\Livewire\Admin\FinanceReport as FinanceReportPage;
use App\Livewire\Admin\Participants;
use App\Livewire\Admin\Settings;
use App\Models\FinanceSubmission;
use App\Models\ParticipantType;
use App\Models\RegistrationGroup;
use App\Models\Setting;
use App\Models\User;
use App\Services\FinanceReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function paidTransferGroup(string $university): RegistrationGroup
    {
        $group = $this->group($this->university($university));
        $this->participant($group, ParticipantType::EXECUTIVE);
        $this->participant($group, ParticipantType::STUDENT);

        $path = 'slips/'.$group->id.'.jpg';
        Storage::disk('local')->put($path, (string) Image::createImage(400, 800)->fill('ccddff')->encode(new JpegEncoder));
        $group->refresh()->forceFill([
            'payment_method' => PaymentMethod::Transfer,
            'payment_status' => PaymentStatus::Approved,
            'slip_path' => $path,
            'transferred_at' => now()->subDay(),
            'approved_at' => now(),
        ])->save();

        return $group;
    }

    public function test_finance_submission_has_only_paid_and_never_duplicates(): void
    {
        $paid = $this->paidTransferGroup('มหาวิทยาลัยราชภัฏสุราษฎร์ธานี');

        $onsite = $this->group($this->university('มหาวิทยาลัยราชภัฏภูเก็ต'));
        $this->participant($onsite, ParticipantType::STAFF);
        $onsite->refresh()->forceFill(['payment_method' => PaymentMethod::Onsite, 'payment_status' => PaymentStatus::OnsitePaid, 'onsite_received_at' => now()])->save();

        $unpaid = $this->group($this->university('มหาวิทยาลัยราชภัฏยะลา'));
        $this->participant($unpaid);

        $admin = $this->admin();
        Livewire::actingAs($admin)->test(FinanceReportPage::class)->call('create');

        $submission = FinanceSubmission::query()->sole();
        $this->assertSame('RSC20-FIN-001', $submission->submission_no);
        $this->assertSame(3, $submission->item_count);
        $this->assertSame('6000.00', $submission->total_amount); // 2500 + 1500 + 2000
        $this->assertFalse($submission->participants->contains('registration_group_id', $unpaid->id));

        $data = FinanceReport::data($submission);
        $this->assertSame(6000.0, $data['total']);
        $this->assertSame('หกพันบาทถ้วน', $data['totalText']);
        $this->assertCount(2, $data['evidence']);
        $this->assertSame($paid->slip_path, $data['evidence']->firstWhere('group.id', $paid->id)['slip_path']);
        $this->assertNull($data['evidence']->firstWhere('group.id', $onsite->id)['slip_path']);

        // nothing new left to submit
        $this->assertSame(0, FinanceReport::query(['only_new' => true])->count());
        $this->expectException(HttpException::class);
        FinanceReport::create(['date_from' => null, 'date_to' => null], $admin);
    }

    public function test_finance_downloads(): void
    {
        $this->paidTransferGroup('มหาวิทยาลัยราชภัฏสุราษฎร์ธานี');
        $admin = $this->admin();
        $submission = FinanceReport::create(['date_from' => null, 'date_to' => null], $admin);

        $pdf = $this->actingAs($admin)->get(route('admin.finance.download', [$submission, 'pdf']));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertGreaterThanOrEqual(2, preg_match_all('/\/Type\s*\/Page[^s]/', $pdf->getContent())); // report + slip appendix

        $this->actingAs($admin)->get(route('admin.finance.download', [$submission, 'xlsx']))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('admin.finance.download', [$submission, 'zip']))->assertOk()->assertDownload();
    }

    public function test_finance_report_filters_by_status_including_onsite_pending(): void
    {
        $transfer = $this->paidTransferGroup('มหาวิทยาลัยราชภัฏสุราษฎร์ธานี');

        $onsitePaid = $this->group($this->university('มหาวิทยาลัยราชภัฏภูเก็ต'));
        $this->participant($onsitePaid, ParticipantType::STAFF);
        $onsitePaid->refresh()->forceFill(['payment_method' => PaymentMethod::Onsite, 'payment_status' => PaymentStatus::OnsitePaid, 'onsite_received_at' => now()])->save();

        $onsitePending = $this->group($this->university('มหาวิทยาลัยราชภัฏยะลา'));
        $this->participant($onsitePending);
        $onsitePending->refresh()->forceFill(['payment_method' => PaymentMethod::Onsite, 'payment_status' => PaymentStatus::Pending])->save();

        $this->participant($this->group($this->university('มหาวิทยาลัยราชภัฏนครศรีธรรมราช'))); // no method chosen

        $groupsFor = fn (array $filters) => FinanceReport::query($filters)->pluck('registration_group_id')->unique()->sort()->values()->all();

        $this->assertSame(collect([$transfer->id, $onsitePaid->id, $onsitePending->id])->sort()->values()->all(), $groupsFor([]));
        $this->assertSame(collect([$transfer->id, $onsitePaid->id])->sort()->values()->all(), $groupsFor(['date_from' => now()->subDay()->toDateString()]));
        $this->assertSame([$transfer->id], $groupsFor(['status' => 'approved']));
        $this->assertSame([$onsitePaid->id], $groupsFor(['status' => 'onsite_paid']));
        $this->assertSame([$onsitePending->id], $groupsFor(['status' => 'onsite_pending', 'date_from' => now()->addWeek()->toDateString()]));
        $this->assertSame(collect([$transfer->id, $onsitePending->id])->sort()->values()->all(), $groupsFor(['status' => ['approved', 'onsite_pending']]));
        $this->assertSame(collect([$transfer->id, $onsitePaid->id])->sort()->values()->all(), $groupsFor(['university_id' => [$transfer->university_id, $onsitePaid->university_id]]));

        $admin = $this->admin();
        Livewire::actingAs($admin)->test(FinanceReportPage::class)
            ->set('filters.status', ['onsite_pending'])
            ->call('create')
            ->assertHasNoErrors();

        $submission = FinanceSubmission::query()->sole();
        $this->assertSame([$onsitePending->id], $submission->participants->pluck('registration_group_id')->unique()->values()->all());
        $this->actingAs($admin)->get(route('admin.finance.download', [$submission, 'pdf']))->assertOk();
    }

    public function test_participants_page_filters_by_several_universities_and_types(): void
    {
        $surat = $this->participant($this->group($this->university('มหาวิทยาลัยราชภัฏสุราษฎร์ธานี')));
        $phuket = $this->participant($this->group($this->university('มหาวิทยาลัยราชภัฏภูเก็ต')), ParticipantType::STAFF);
        $yala = $this->participant($this->group($this->university('มหาวิทยาลัยราชภัฏยะลา')));

        $page = Livewire::actingAs($this->admin())->test(Participants::class)
            ->set('university', [(string) $surat->group->university_id, (string) $phuket->group->university_id]);

        $page->assertSee($surat->code)->assertSee($phuket->code)->assertDontSee($yala->code);

        $page->set('type', [(string) $phuket->participant_type_id])
            ->assertSee($phuket->code)->assertDontSee($surat->code)->assertDontSee($yala->code);
    }

    public function test_excel_exports_download(): void
    {
        $this->paidTransferGroup('มหาวิทยาลัยราชภัฏสุราษฎร์ธานี');
        $admin = $this->admin();
        Livewire::actingAs($admin)->test(Settings::class)->set('badgeBackground', UploadedFile::fake()->image('bg.png', 1003, 1568));

        foreach (array_keys(ExportController::TYPES) as $type) {
            $this->actingAs($admin)->get(route('admin.exports.download', $type))->assertOk()->assertDownload();
        }
        $this->actingAs($admin)->get(route('admin.exports.download', ['participants', 'with_id' => 1]))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'export.participants']);
    }

    public function test_board_game_export_lists_only_team_members_with_per_university_counts(): void
    {
        Excel::fake();
        $this->travelTo(now()->setTime(9, 0));
        $group = $this->group();
        $staff = $this->participant($group, ParticipantType::STAFF, ['attendance' => Attendance::BoardGame]);
        $student = $this->participant($group, ParticipantType::STUDENT, ['attendance' => Attendance::ConferenceAndBoardGame]);
        $this->participant($group, ParticipantType::STUDENT);

        $this->actingAs($this->admin())->get(route('admin.exports.download', 'board_game'));

        Excel::assertDownloaded('rsc20-board_game-'.now()->format('Ymd-Hi').'.xlsx', function (Workbook $workbook) use ($staff, $student) {
            [$teams, $list] = $workbook->sheets();

            $this->assertSame([['มหาวิทยาลัยราชภัฏสุราษฎร์ธานี', 1, 1, 2], ['รวม', 1, 1, 2]], $teams->array());
            $this->assertSame([$staff->code, $student->code], array_column($list->array(), 1));

            return true;
        });
    }

    public function test_admin_uploads_badge_background_and_downloads_badge_pdf(): void
    {
        $group = $this->group();
        $withPhoto = $this->participant($group);
        Storage::disk('local')->put('badges/'.$withPhoto->code.'.jpg', (string) Image::createImage(600, 600)->fill('aabbcc')->encode(new JpegEncoder));
        $withPhoto->forceFill(['badge_photo_path' => 'badges/'.$withPhoto->code.'.jpg'])->saveQuietly();
        $this->participant($group);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.exports.download', 'badge_pdf'))->assertStatus(422);

        Livewire::actingAs($admin)->test(Settings::class)
            ->set('badgeBackground', UploadedFile::fake()->image('bg.png', 1003, 1568))
            ->assertHasNoErrors();

        Storage::disk('local')->assertExists(Settings::BADGE_BACKGROUND_PATH);
        $this->assertSame(Settings::BADGE_BACKGROUND_PATH, Setting::get('badge_background_path'));
        $this->actingAs($admin)->get(route('admin.badge-background'))->assertOk();

        $response = $this->actingAs($admin)->get(route('admin.exports.download', 'badge_pdf'))->assertOk()->assertDownload();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_badge_background_rejects_non_images_and_non_admins(): void
    {
        Livewire::actingAs($this->admin())->test(Settings::class)
            ->set('badgeBackground', UploadedFile::fake()->create('bg.pdf', 10, 'application/pdf'))
            ->assertHasErrors('badgeBackground');
        Storage::disk('local')->assertMissing(Settings::BADGE_BACKGROUND_PATH);

        $this->actingAs($this->coordinator())->get(route('admin.badge-background'))->assertForbidden();
    }

    public function test_settings_save_opens_the_section_holding_a_rejected_field(): void
    {
        $page = Livewire::actingAs($this->admin())->test(Settings::class)
            ->assertSet('section', 'event');

        $page->set('fees', array_map(fn () => '-5', $page->get('fees')))
            ->call('save')
            ->assertHasErrors('fees.*')
            ->assertSet('section', 'fees');
    }

    public function test_settings_rejects_an_invalid_participant_code_format(): void
    {
        Livewire::actingAs($this->admin())->test(Settings::class)
            ->set('settings.participant_code_prefix', 'RSC 20/')
            ->set('settings.participant_code_digits', '9')
            ->call('save')
            ->assertHasErrors(['settings.participant_code_prefix', 'settings.participant_code_digits'])
            ->assertSet('section', 'code');

        $this->assertSame('RSC20-', Setting::get('participant_code_prefix'));
    }

    public function test_admin_issues_coordinator_accounts_for_all_universities(): void
    {
        $admin = $this->admin();

        $page = Livewire::actingAs($admin)->test(CoordinatorAccounts::class)->call('createMissing');

        $this->assertSame(38, User::query()->where('role', 'coordinator')->count());
        $issued = $page->get('issued');
        $this->assertCount(38, $issued);

        $first = User::query()->where('username', $issued[0]['username'])->first();
        $this->assertTrue($first->must_change_password);
        $this->assertNotSame($issued[0]['password'], $first->password); // hashed

        $page->call('downloadIssued')->assertFileDownloaded('rsc20-coordinator-accounts.pdf');

        $this->travelTo(now()->setDateTime(2026, 11, 3, 9, 30));
        $page->call('downloadIssuedExcel')->assertFileDownloaded('rsc20-coordinator-accounts-20261103-0930.xlsx');
        $this->assertDatabaseHas('audit_logs', ['action' => 'coordinator.credentials_exported']);
    }
}
