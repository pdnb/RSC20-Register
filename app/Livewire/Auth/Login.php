<?php

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Coordinator sign-in with the username + password an admin issued. There is no self-registration. */
#[Layout('layouts.guest')]
#[Title('เข้าสู่ระบบผู้ประสานงาน')]
class Login extends Component
{
    public string $username = '';

    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ], attributes: ['username' => 'ชื่อผู้ใช้', 'password' => 'รหัสผ่าน']);

        $key = 'coordinator-login:'.Str::lower($this->username).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'username' => 'พยายามเข้าสู่ระบบหลายครั้งเกินไป กรุณารอ '.RateLimiter::availableIn($key).' วินาที',
            ]);
        }

        $user = User::query()
            ->where('role', Role::Coordinator)
            ->where('username', Str::lower(trim($this->username)))
            ->first();

        if (! $user || ! $user->password || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['username' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['username' => 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ']);
        }

        RateLimiter::clear($key);
        Auth::login($user, $this->remember);
        session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return $this->redirect($user->must_change_password ? route('coordinator.password') : route('coordinator.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
