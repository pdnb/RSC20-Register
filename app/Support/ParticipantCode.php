<?php

namespace App\Support;

use App\Models\Participant;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/** Issues participant codes from the admin-set prefix, digit count and start number. */
class ParticipantCode
{
    /** Gives the participant the next free code; serialised so two registrations never draw the same number. */
    public static function assign(Participant $participant): void
    {
        Cache::lock('participant-code', 10)->block(5, function () use ($participant): void {
            $participant->forceFill(['code' => self::next()])->saveQuietly();
        });
    }

    /** The code the next participant will receive under the saved settings. */
    public static function next(): string
    {
        return self::preview(
            (string) Setting::get('participant_code_prefix'),
            (int) Setting::get('participant_code_digits'),
            (int) Setting::get('participant_code_start'),
        );
    }

    /**
     * The next code for the given format: one past the highest number already issued under
     * this prefix, but never below the start number.
     */
    public static function preview(string $prefix, int $digits, int $start): string
    {
        $highest = Participant::query()
            ->where('code', 'like', str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefix).'%')
            ->pluck('code')
            ->map(fn (string $code) => substr($code, strlen($prefix)))
            ->filter(fn (string $number) => ctype_digit($number))
            ->map(fn (string $number) => (int) $number)
            ->max() ?? 0;

        return $prefix.str_pad((string) max($start, $highest + 1), max($digits, 1), '0', STR_PAD_LEFT);
    }
}
