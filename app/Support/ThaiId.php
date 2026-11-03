<?php

namespace App\Support;

class ThaiId
{
    public static function normalize(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    /** 13-digit Thai citizen / taxpayer ID with mod-11 checksum. */
    public static function isValid(?string $value): bool
    {
        $id = self::normalize($value);

        if (strlen($id) !== 13) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $id[$i] * (13 - $i);
        }

        return (11 - ($sum % 11)) % 10 === (int) $id[12];
    }

    /** Keyed hash so the encrypted ID can still be checked for duplicates. */
    public static function hash(string $value): string
    {
        return hash_hmac('sha256', self::normalize($value), (string) config('app.key'));
    }

    public static function format(?string $value): string
    {
        $id = self::normalize($value);

        return strlen($id) === 13
            ? substr($id, 0, 1).'-'.substr($id, 1, 4).'-'.substr($id, 5, 5).'-'.substr($id, 10, 2).'-'.substr($id, 12, 1)
            : (string) $value;
    }

    public static function mask(?string $value): string
    {
        $id = self::normalize($value);

        return strlen($id) === 13 ? 'x-xxxx-xxxxx-'.substr($id, 10, 2).'-'.substr($id, 12, 1) : '-';
    }
}
