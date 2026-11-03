<?php

namespace Tests;

use App\Enums\Role;
use App\Enums\Section;
use App\Models\Participant;
use App\Models\ParticipantType;
use App\Models\RegistrationGroup;
use App\Models\University;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Valid 13-digit IDs (checksum ok), handed out in order. */
    private int $idSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        ParticipantType::query()->where('code', ParticipantType::EXECUTIVE)->update(['fee' => 2500]);
        ParticipantType::query()->where('code', ParticipantType::STAFF)->update(['fee' => 2000]);
        ParticipantType::query()->where('code', ParticipantType::STUDENT)->update(['fee' => 1500]);
    }

    protected function nextNationalId(): string
    {
        $base = str_pad((string) (110000000000 + ++$this->idSeq), 12, '0', STR_PAD_LEFT);
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $base[$i] * (13 - $i);
        }

        return $base.((11 - ($sum % 11)) % 10);
    }

    protected function university(string $name = 'มหาวิทยาลัยราชภัฏสุราษฎร์ธานี'): University
    {
        return University::query()->where('name', $name)->firstOrFail();
    }

    protected function coordinator(?University $university = null, array $attrs = []): User
    {
        static $n = 0;

        return User::query()->create([
            'name' => 'ผู้ประสานงาน',
            'role' => Role::Coordinator,
            'university_id' => ($university ?? $this->university())->id,
            'username' => 'coord'.++$n,
            'password' => 'Secret123',
            'is_active' => true,
            ...$attrs,
        ]);
    }

    protected function admin(): User
    {
        return User::query()->create(['name' => 'Admin', 'role' => Role::Admin, 'email' => 'admin'.uniqid().'@example.test', 'is_active' => true]);
    }

    protected function staff(): User
    {
        return User::query()->create(['name' => 'Staff', 'role' => Role::Staff, 'email' => 'staff'.uniqid().'@example.test', 'is_active' => true]);
    }

    protected function group(?University $university = null): RegistrationGroup
    {
        return RegistrationGroup::query()->firstOrCreate(['university_id' => ($university ?? $this->university())->id]);
    }

    protected function participant(RegistrationGroup $group, string $typeCode = ParticipantType::STUDENT, array $attrs = []): Participant
    {
        $id = $this->nextNationalId();

        return Participant::query()->create([
            'registration_group_id' => $group->id,
            'participant_type_id' => ParticipantType::byCode($typeCode)->id,
            'section' => $typeCode === ParticipantType::EXECUTIVE ? Section::Executive : Section::Member,
            'position' => match ($typeCode) {
                ParticipantType::EXECUTIVE => 'รองอธิการบดี',
                ParticipantType::STAFF => 'บุคลากร',
                default => 'นักศึกษา',
            },
            'title' => 'นาย',
            'full_name' => 'ทดสอบ ระบบ'.$this->idSeq,
            'mobile_phone' => '0812345678',
            'national_id' => $id,
            'food_type' => 'อาหารทั่วไป',
            'shirt_size' => 'M-M',
            'receipt_name' => 'นายทดสอบ ระบบ',
            'receipt_address' => '272 ถ.สุราษฎร์-นาสาร อ.เมือง จ.สุราษฎร์ธานี',
            'receipt_tax_id' => $id,
            ...$attrs,
        ]);
    }
}
