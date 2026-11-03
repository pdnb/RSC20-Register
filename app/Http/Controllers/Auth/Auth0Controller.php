<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;
use Throwable;

class Auth0Controller extends Controller
{
    public static function isConfigured(): bool
    {
        return filled(config('services.auth0.domain'))
            && filled(config('services.auth0.client_id'))
            && filled(config('services.auth0.client_secret'));
    }

    public function redirect(): RedirectResponse
    {
        if (! self::isConfigured()) {
            return $this->fail('ยังไม่ได้ตั้งค่า Auth0 (AUTH0_DOMAIN / AUTH0_CLIENT_ID / AUTH0_CLIENT_SECRET)');
        }

        return Socialite::driver('auth0')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! self::isConfigured()) {
            return $this->fail('ยังไม่ได้ตั้งค่า Auth0');
        }

        try {
            $oauthUser = Socialite::driver('auth0')->user();
        } catch (Throwable $e) {
            report($e);

            return $this->fail('เข้าสู่ระบบด้วย Auth0 ไม่สำเร็จ กรุณาลองใหม่');
        }

        $sub = (string) $oauthUser->getId();
        $email = Str::lower(trim((string) $oauthUser->getEmail()));
        $verified = (bool) ($oauthUser->getRaw()['email_verified'] ?? false);

        if ($sub === '' || $email === '' || ! $verified) {
            AuditLog::record('auth0.rejected', null, ['email' => $email, 'reason' => 'unverified']);

            return $this->fail('บัญชี Auth0 ต้องมีอีเมลที่ยืนยันแล้ว');
        }

        // Allowlist: the user must already exist as admin/staff, matched by Auth0 sub or email.
        $user = User::query()
            ->whereIn('role', [Role::Admin, Role::Staff])
            ->where(fn ($q) => $q->where('auth0_sub', $sub)->orWhere('email', $email))
            ->first();

        if (! $user || ! $user->is_active || ($user->auth0_sub && $user->auth0_sub !== $sub)) {
            AuditLog::record('auth0.rejected', null, ['email' => $email, 'reason' => 'not_allowlisted']);

            return $this->fail("อีเมล {$email} ไม่มีสิทธิ์เข้าใช้งานส่วนผู้ดูแล");
        }

        $user->forceFill([
            'auth0_sub' => $sub,
            'name' => $user->name ?: (string) $oauthUser->getName(),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('auth0.login', $user);

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('staff.checkin'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (! self::isConfigured()) {
            return redirect()->route('admin.login');
        }

        $domain = rtrim((string) config('services.auth0.domain'), '/');
        $domain = str_starts_with($domain, 'http') ? $domain : 'https://'.$domain;

        return redirect()->away($domain.'/v2/logout?'.http_build_query([
            'client_id' => config('services.auth0.client_id'),
            'returnTo' => route('admin.login'),
        ]));
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('admin.login')->withErrors(['auth0' => $message]);
    }
}
