<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    public const DEFAULTS = [
        'registration_opens_at' => null,
        'registration_closes_at' => null,
        'event_name' => 'สัมมนาสมาพันธ์นักศึกษามหาวิทยาลัยราชภัฏแห่งประเทศไทย ครั้งที่ 20',
        'event_dates' => '',
        'event_venue' => 'มหาวิทยาลัยราชภัฏสุราษฎร์ธานี',
        'bank_name' => '',
        'bank_account_name' => '',
        'bank_account_no' => '',
        'pdpa_text' => 'ข้าพเจ้ายินยอมให้มหาวิทยาลัยราชภัฏสุราษฎร์ธานีเก็บ ใช้ และเปิดเผยข้อมูลส่วนบุคคลของผู้เข้าร่วม รวมถึงเลขประจำตัวประชาชน เพื่อการจัดสัมมนา การออกใบเสร็จรับเงิน และยืนยันว่าได้รับความยินยอมจากผู้เข้าร่วมทุกคนแล้ว',
        'finance_office_name' => 'กองคลัง มหาวิทยาลัยราชภัฏสุราษฎร์ธานี',
        'finance_sender_name' => '',
        // Participant code = prefix + running number padded to the digit count, starting at start
        'participant_code_prefix' => 'RSC20-',
        'participant_code_digits' => '4',
        'participant_code_start' => '1',
        // Name badge: photo box and text start as % of the signed backgrounds
        'badge_photo_left' => '33.8',
        'badge_photo_top' => '34.1',
        'badge_photo_width' => '32.6',
        'badge_photo_height' => '25.0',
        'badge_text_top' => '60.0',
        // LINE group for coordinators: invite link and/or an uploaded QR image
        'line_group_url' => '',
        'line_qr_path' => null,
    ];

    /** Keys kept by the system rather than typed into the settings form. */
    public const MANAGED = ['line_qr_path'];

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key): ?string
    {
        $all = Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all());

        return filled($all[$key] ?? null) ? $all[$key] : (self::DEFAULTS[$key] ?? null);
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings');
    }

    /** URL of the uploaded LINE group QR image, versioned so a replacement is not served from cache. */
    public static function lineQrUrl(): ?string
    {
        $path = self::get('line_qr_path');

        return $path && Storage::disk('local')->exists($path)
            ? route('files.line-qr', ['v' => Storage::disk('local')->lastModified($path)])
            : null;
    }

    public static function hasLineGroup(): bool
    {
        return filled(self::get('line_group_url')) || self::lineQrUrl() !== null;
    }

    public static function registrationOpen(): bool
    {
        $opens = self::get('registration_opens_at');
        $closes = self::get('registration_closes_at');
        $now = now();

        return (! $opens || $now->gte(Carbon::parse($opens)))
            && (! $closes || $now->lte(Carbon::parse($closes)));
    }
}
