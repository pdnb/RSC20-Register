<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Exports\ArraySheet;
use App\Exports\Workbook;
use App\Models\AuditLog;
use App\Models\University;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.admin')]
#[Title('บัญชีผู้ประสานงาน')]
class CoordinatorAccounts extends Component
{
    public array $form = ['university_id' => '', 'username' => '', 'name' => '', 'phone' => ''];

    /**
     * Credentials issued in this page visit only. Passwords are shown once and never stored in plain text.
     *
     * @var list<array{university: string, username: string, password: string, name: string}>
     */
    public array $issued = [];

    public function create(): void
    {
        $this->form = ['university_id' => '', 'username' => '', 'name' => '', 'phone' => ''];
        $this->resetValidation();
        $this->dispatch('wirekit-modal-show', name: 'coordinator-form');
    }

    public function updatedFormUniversityId($id): void
    {
        if ($id && $this->form['username'] === '') {
            $this->form['username'] = $this->suggestUsername(University::query()->find($id));
        }
    }

    public function save(): void
    {
        $this->form['username'] = Str::lower(trim($this->form['username']));

        $this->validate([
            'form.university_id' => ['required', 'exists:universities,id'],
            'form.username' => ['required', 'regex:/^[a-z0-9._-]{4,50}$/', Rule::unique('users', 'username')],
            'form.name' => ['required', 'string', 'max:150'],
            'form.phone' => ['nullable', 'string', 'max:20'],
        ], [
            'form.username.regex' => 'username ใช้ได้เฉพาะ a-z 0-9 . _ - ความยาว 4–50 ตัว',
        ], [
            'form.university_id' => 'มหาวิทยาลัย',
            'form.username' => 'username',
            'form.name' => 'ชื่อผู้ประสานงาน',
        ]);

        $this->issue(University::query()->findOrFail($this->form['university_id']), $this->form['username'], $this->form['name'], $this->form['phone'] ?: null);
        $this->dispatch('wirekit-modal-close', name: 'coordinator-form');
    }

    /** One account for every university that has none yet. */
    public function createMissing(): void
    {
        University::query()->orderBy('sort_order')
            ->whereDoesntHave('coordinators', fn ($q) => $q->where('role', Role::Coordinator))
            ->get()
            ->each(fn (University $u) => $this->issue($u, $this->suggestUsername($u), 'ผู้ประสานงาน '.$u->name, null));

        $this->dispatch('wirekit-toast', variant: 'success', title: 'สร้างบัญชีแล้ว', message: count($this->issued).' บัญชี');
    }

    public function resetPassword(int $id): void
    {
        $user = User::query()->where('role', Role::Coordinator)->findOrFail($id);
        $password = $this->newPassword();
        $user->forceFill(['password' => $password, 'must_change_password' => true])->save();

        AuditLog::record('coordinator.password_reset', $user);
        $this->issued[] = ['university' => $user->university->name, 'username' => $user->username, 'password' => $password, 'name' => $user->name];
    }

    public function toggleActive(int $id): void
    {
        $user = User::query()->where('role', Role::Coordinator)->findOrFail($id);
        $user->forceFill(['is_active' => ! $user->is_active])->save();
        AuditLog::record($user->is_active ? 'coordinator.activated' : 'coordinator.suspended', $user);
    }

    public function downloadIssued()
    {
        abort_if($this->issued === [], 404);
        AuditLog::record('coordinator.credentials_exported', null, ['count' => count($this->issued), 'format' => 'pdf']);

        $pdf = Pdf::loadView('pdf.coordinator-credentials', ['rows' => $this->issued, 'loginUrl' => route('login')])->setPaper('a4');

        return response()->streamDownload(fn () => print ($pdf->output()), 'rsc20-coordinator-accounts.pdf');
    }

    public function downloadIssuedExcel()
    {
        abort_if($this->issued === [], 404);
        AuditLog::record('coordinator.credentials_exported', null, ['count' => count($this->issued), 'format' => 'xlsx']);

        $loginUrl = route('login');
        $rows = collect($this->issued)->values()->map(fn (array $row, int $i) => [
            $i + 1, $row['university'], $row['name'], $row['username'], $row['password'], $loginUrl,
        ])->all();

        return Excel::download(
            new Workbook([new ArraySheet('บัญชีผู้ประสานงาน', ['ลำดับ', 'มหาวิทยาลัย', 'ชื่อผู้ประสานงาน', 'username', 'รหัสผ่าน', 'URL เข้าสู่ระบบ'], $rows)]),
            'rsc20-coordinator-accounts-'.now()->format('Ymd-Hi').'.xlsx',
        );
    }

    private function issue(University $university, string $username, string $name, ?string $phone): void
    {
        $password = $this->newPassword();

        $user = User::query()->create([
            'name' => $name,
            'role' => Role::Coordinator,
            'university_id' => $university->id,
            'username' => $username,
            'phone' => $phone,
            'password' => $password,
            'must_change_password' => true,
            'is_active' => true,
        ]);

        AuditLog::record('coordinator.created', $user);
        $this->issued[] = ['university' => $university->name, 'username' => $username, 'password' => $password, 'name' => $name];
    }

    private function suggestUsername(?University $university): string
    {
        $base = 'rusf20_'.Str::lower((string) $university?->code);
        $username = $base;
        $i = 2;
        while (User::query()->where('username', $username)->exists()) {
            $username = $base.'-'.$i++;
        }

        return $username;
    }

    private function newPassword(): string
    {
        // No look-alike characters, so it can be read off a printed sheet.
        return collect(range(1, 10))->map(fn () => 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 54)])->join('');
    }

    public function render()
    {
        return view('livewire.admin.coordinator-accounts', [
            'accounts' => User::query()->where('role', Role::Coordinator)->with('university')->orderBy('university_id')->get(),
            'universities' => University::query()->orderBy('sort_order')->pluck('name', 'id'),
            'missing' => University::query()->whereDoesntHave('coordinators', fn ($q) => $q->where('role', Role::Coordinator))->count(),
        ]);
    }
}
