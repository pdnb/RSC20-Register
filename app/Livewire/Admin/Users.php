<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Auth0 allowlist: who may sign in as admin or check-in staff. */
#[Layout('layouts.admin')]
#[Title('ผู้ดูแล / เจ้าหน้าที่')]
class Users extends Component
{
    public string $email = '';

    public string $name = '';

    public string $role = 'staff';

    public function add(): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', Rule::in([Role::Admin->value, Role::Staff->value])],
        ], attributes: ['email' => 'อีเมล', 'name' => 'ชื่อ', 'role' => 'สิทธิ์']);

        $user = User::query()->create(['email' => $this->email, 'name' => $this->name, 'role' => $this->role, 'is_active' => true]);
        AuditLog::record('user.allowlisted', $user, ['role' => $this->role]);

        $this->reset('email', 'name');
        $this->dispatch('wirekit-toast', variant: 'success', title: 'เพิ่มสิทธิ์แล้ว', message: $user->email);
    }

    public function toggleActive(int $id): void
    {
        abort_if($id === auth()->id(), 422, 'ระงับบัญชีตัวเองไม่ได้');
        $user = User::query()->whereIn('role', [Role::Admin, Role::Staff])->findOrFail($id);
        $user->forceFill(['is_active' => ! $user->is_active])->save();
        AuditLog::record($user->is_active ? 'user.activated' : 'user.suspended', $user);
    }

    public function render()
    {
        return view('livewire.admin.users', [
            'users' => User::query()->whereIn('role', [Role::Admin, Role::Staff])->orderBy('role')->orderBy('email')->get(),
        ]);
    }
}
