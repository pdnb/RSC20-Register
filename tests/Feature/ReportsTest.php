<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Admin\ExportController;
use App\Livewire\Admin\CoordinatorAccounts;
use App\Livewire\Admin\FinanceReport as FinanceReportPage;
use App\Models\FinanceSubmission;
use App\Models\ParticipantType;
use App\Models\RegistrationGroup;
use App\Models\User;
use App\Services\FinanceReport;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Livewire;
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

        $admin = $this->admin();
        Livewire::actingAs($admin)->test(FinanceReportPage::class)
            ->set('filters.status', 'onsite_pending')
            ->call('create')
            ->assertHasNoErrors();

        $submission = FinanceSubmission::query()->sole();
        $this->assertSame([$onsitePending->id], $submission->participants->pluck('registration_group_id')->unique()->values()->all());
        $this->actingAs($admin)->get(route('admin.finance.download', [$submission, 'pdf']))->assertOk();
    }

    public function test_excel_exports_download(): void
    {
        $this->paidTransferGroup('มหาวิทยาลัยราชภัฏสุราษฎร์ธานี');
        $admin = $this->admin();

        foreach (array_keys(ExportController::TYPES) as $type) {
            $this->actingAs($admin)->get(route('admin.exports.download', $type))->assertOk()->assertDownload();
        }
        $this->actingAs($admin)->get(route('admin.exports.download', ['participants', 'with_id' => 1]))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'export.participants']);
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
