<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Previous defaults, replaced only when a row still has that exact value. */
    private const PREVIOUS = [
        'badge_photo_left' => '34.5',
        'badge_photo_top' => '33.4',
        'badge_photo_width' => '31',
        'badge_photo_height' => '23.4',
        'badge_text_top' => '58.5',
    ];

    private const NEXT = [
        'badge_photo_left' => '33.8',
        'badge_photo_top' => '34.1',
        'badge_photo_width' => '32.6',
        'badge_photo_height' => '25.0',
        'badge_text_top' => '60.0',
    ];

    public function up(): void
    {
        foreach (self::PREVIOUS as $key => $value) {
            DB::table('settings')->where('key', $key)->where('value', $value)->update(['value' => self::NEXT[$key]]);
        }

        foreach (DB::table('universities')->where('name', 'not like', 'มหาวิทยาลัย%')->get() as $university) {
            DB::table('universities')->where('id', $university->id)->update([
                'name' => 'มหาวิทยาลัยราชภัฏ'.$university->name,
            ]);
        }

        DB::table('settings')->where('key', 'badge_background_path')->delete();
    }

    public function down(): void
    {
        foreach (self::NEXT as $key => $value) {
            DB::table('settings')->where('key', $key)->where('value', $value)->update(['value' => self::PREVIOUS[$key]]);
        }

        foreach (DB::table('universities')->where('name', 'like', 'มหาวิทยาลัยราชภัฏ%')->get() as $university) {
            DB::table('universities')->where('id', $university->id)->update([
                'name' => mb_substr($university->name, mb_strlen('มหาวิทยาลัยราชภัฏ')),
            ]);
        }
    }
};
