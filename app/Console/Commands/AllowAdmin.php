<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('admin:allow {email} {--role=admin : admin หรือ staff} {--name=}')]
#[Description('Add an email to the Auth0 allowlist as admin or staff')]
class AllowAdmin extends Command
{
    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $role = Role::tryFrom($this->option('role'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! in_array($role, [Role::Admin, Role::Staff], true)) {
            $this->error('ต้องระบุอีเมลที่ถูกต้อง และ --role=admin หรือ --role=staff');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(['email' => $email], [
            'name' => $this->option('name') ?: Str::before($email, '@'),
            'role' => $role,
            'is_active' => true,
        ]);

        $this->info("{$user->email} → {$role->label()} (เข้าสู่ระบบที่ ".route('admin.login').')');

        return self::SUCCESS;
    }
}
