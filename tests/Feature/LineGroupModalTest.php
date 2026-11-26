<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\Setting;
use App\Models\University;
use App\Support\QrCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LineGroupModalTest extends TestCase
{
    private const INVITE = 'https://line.me/ti/g/abc123';

    /** Present only in the element that opens the modal without a click. */
    private const AUTO_OPEN = '$el.remove()';

    /** Independent of the seeded university names, unlike the TestCase default. */
    private function anyUniversity(): University
    {
        return University::query()->orderBy('id')->firstOrFail();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_coordinator_sees_nothing_until_the_group_is_configured(): void
    {
        $this->actingAs($this->coordinator($this->anyUniversity()))->get(route('coordinator.dashboard'))
            ->assertOk()
            ->assertDontSee('line-group')
            ->assertSessionMissing('line_group_modal_shown');
    }

    public function test_an_invite_link_shows_a_join_button_and_a_generated_qr(): void
    {
        Setting::put(['line_group_url' => self::INVITE]);

        $this->actingAs($this->coordinator($this->anyUniversity()))->get(route('coordinator.dashboard'))
            ->assertOk()
            ->assertSee('href="'.self::INVITE.'"', false)
            ->assertSee(QrCode::svg(self::INVITE, 280), false);
    }

    public function test_an_uploaded_image_alone_is_shown_without_a_join_button(): void
    {
        Livewire::actingAs($this->admin())->test(Settings::class)
            ->set('lineQr', UploadedFile::fake()->image('qr.png', 600, 600))
            ->assertHasNoErrors();

        $this->actingAs($this->coordinator($this->anyUniversity()))->get(route('coordinator.dashboard'))
            ->assertOk()
            ->assertSee(Setting::lineQrUrl())
            ->assertDontSee('เข้าร่วมกลุ่ม LINE');
    }

    public function test_modal_opens_by_itself_only_on_the_first_page_of_a_session(): void
    {
        Setting::put(['line_group_url' => self::INVITE]);
        $this->actingAs($this->coordinator($this->anyUniversity()));

        $this->get(route('coordinator.dashboard'))->assertSee(self::AUTO_OPEN, false);

        $this->get(route('coordinator.payment'))
            ->assertDontSee(self::AUTO_OPEN, false)
            ->assertSee('href="'.self::INVITE.'"', false);
    }

    public function test_modal_opens_again_after_logging_out(): void
    {
        Setting::put(['line_group_url' => self::INVITE]);
        $coordinator = $this->coordinator($this->anyUniversity());
        $this->actingAs($coordinator)->get(route('coordinator.dashboard'));

        $this->post(route('logout'));

        $this->actingAs($coordinator)->get(route('coordinator.dashboard'))->assertSee(self::AUTO_OPEN, false);
    }

    public function test_admin_viewing_a_group_does_not_get_the_modal(): void
    {
        Setting::put(['line_group_url' => self::INVITE]);

        $this->actingAs($this->admin())->get(route('admin.groups.show', $this->group($this->anyUniversity())))
            ->assertOk()
            ->assertDontSee('line-group');
    }

    public function test_admin_saves_the_invite_link(): void
    {
        Livewire::actingAs($this->admin())->test(Settings::class)
            ->set('settings.line_group_url', self::INVITE)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(self::INVITE, Setting::get('line_group_url'));
    }

    public function test_an_invite_link_that_is_not_https_is_rejected(): void
    {
        Livewire::actingAs($this->admin())->test(Settings::class)
            ->set('settings.line_group_url', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors('settings.line_group_url')
            ->assertSet('section', 'line');

        $this->assertEmpty(Setting::get('line_group_url'));
    }

    public function test_a_non_image_qr_upload_is_rejected(): void
    {
        Livewire::actingAs($this->admin())->test(Settings::class)
            ->set('lineQr', UploadedFile::fake()->create('qr.pdf', 10, 'application/pdf'))
            ->assertHasErrors('lineQr');

        Storage::disk('local')->assertMissing(Settings::LINE_QR_PATH);
    }

    public function test_admin_removes_the_qr_image(): void
    {
        $page = Livewire::actingAs($this->admin())->test(Settings::class)
            ->set('lineQr', UploadedFile::fake()->image('qr.png', 600, 600));
        Storage::disk('local')->assertExists(Settings::LINE_QR_PATH);

        $page->call('removeLineQr');

        Storage::disk('local')->assertMissing(Settings::LINE_QR_PATH);
        $this->assertFalse(Setting::hasLineGroup());
    }

    public function test_qr_image_is_served_to_coordinators_and_admins_only(): void
    {
        Storage::disk('local')->put(Settings::LINE_QR_PATH, 'image');
        Setting::put(['line_qr_path' => Settings::LINE_QR_PATH]);

        $this->get(route('files.line-qr'))->assertRedirect(route('login'));
        $this->actingAs($this->staff())->get(route('files.line-qr'))->assertForbidden();
        $this->actingAs($this->coordinator($this->anyUniversity()))->get(route('files.line-qr'))->assertOk();
        $this->actingAs($this->admin())->get(route('files.line-qr'))->assertOk();
    }
}
