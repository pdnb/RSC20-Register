<?php

namespace Database\Seeders;

use App\Models\ParticipantType;
use App\Models\University;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** The 38 Rajabhat universities of Thailand. */
    private const UNIVERSITIES = [
        'จันทรเกษม', 'ธนบุรี', 'บ้านสมเด็จเจ้าพระยา', 'พระนคร', 'สวนสุนันทา', 'สวนดุสิต',
        'นครปฐม', 'เพชรบุรี', 'หมู่บ้านจอมบึง', 'กาญจนบุรี', 'เทพสตรี', 'พระนครศรีอยุธยา',
        'ราชนครินทร์', 'รำไพพรรณี', 'วไลยอลงกรณ์ ในพระบรมราชูปถัมภ์',
        'กำแพงเพชร', 'เชียงราย', 'เชียงใหม่', 'นครสวรรค์', 'พิบูลสงคราม', 'ลำปาง', 'อุตรดิตถ์',
        'ชัยภูมิ', 'นครราชสีมา', 'บุรีรัมย์', 'มหาสารคาม', 'ร้อยเอ็ด', 'เลย', 'ศรีสะเกษ',
        'สกลนคร', 'สุรินทร์', 'อุดรธานี', 'อุบลราชธานี',
        'ภูเก็ต', 'ยะลา', 'สงขลา', 'สุราษฎร์ธานี', 'นครศรีธรรมราช',
    ];

    public function run(): void
    {
        foreach (self::UNIVERSITIES as $i => $name) {
            University::query()->updateOrCreate(
                ['code' => sprintf('U%02d', $i + 1)],
                [
                    'name' => $name === 'สวนดุสิต' ? 'มหาวิทยาลัยสวนดุสิต' : 'มหาวิทยาลัยราชภัฏ'.$name,
                    'sort_order' => $i + 1,
                ],
            );
        }

        // Fees start at 0 — the admin sets the real amounts on the settings page.
        foreach ([
            [ParticipantType::EXECUTIVE, 'ผู้บริหาร', 1],
            [ParticipantType::STAFF, 'บุคลากร', 2],
            [ParticipantType::STUDENT, 'นักศึกษา', 3],
        ] as [$code, $name, $order]) {
            ParticipantType::query()->firstOrCreate(['code' => $code], ['name_th' => $name, 'sort_order' => $order, 'fee' => 0]);
        }

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
