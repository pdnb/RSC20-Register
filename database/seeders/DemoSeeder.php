<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\ParticipantType;
use App\Models\University;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Local-only demo accounts; credentials are written to storage/app/private/demo-credentials.txt. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        ParticipantType::query()->where('code', ParticipantType::EXECUTIVE)->update(['fee' => 2500]);
        ParticipantType::query()->where('code', ParticipantType::STAFF)->update(['fee' => 2000]);
        ParticipantType::query()->where('code', ParticipantType::STUDENT)->update(['fee' => 1500]);

        $university = University::query()->where('name', 'มหาวิทยาลัยราชภัฏสุราษฎร์ธานี')->firstOrFail();
        $password = 'Demo-'.bin2hex(random_bytes(4));

        User::query()->updateOrCreate(['username' => 'demo-sru'], [
            'name' => 'ผู้ประสานงานทดสอบ',
            'role' => Role::Coordinator,
            'university_id' => $university->id,
            'password' => $password,
            'must_change_password' => false,
            'is_active' => true,
        ]);

        User::query()->updateOrCreate(['email' => 'admin@example.test'], [
            'name' => 'ผู้ดูแลทดสอบ',
            'role' => Role::Admin,
        ]);

        @mkdir(storage_path('app/private'), 0775, true);
        file_put_contents(
            storage_path('app/private/demo-credentials.txt'),
            "coordinator username: demo-sru\npassword: {$password}\nadmin (Auth0 allowlist): admin@example.test\n",
        );
    }
}
