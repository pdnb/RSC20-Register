<?php

namespace App\Support;

/** Amount in Thai words, e.g. 1250.50 → "หนึ่งพันสองร้อยห้าสิบบาทห้าสิบสตางค์". */
class ThaiBaht
{
    private const DIGITS = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];

    private const UNITS = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน'];

    public static function text(float|int|string $amount): string
    {
        $amount = round((float) $amount, 2);
        $baht = (int) floor($amount);
        $satang = (int) round(($amount - $baht) * 100);

        if ($baht === 0 && $satang === 0) {
            return 'ศูนย์บาทถ้วน';
        }

        $text = $baht > 0 ? self::number($baht).'บาท' : '';

        return $text.($satang > 0 ? self::number($satang).'สตางค์' : 'ถ้วน');
    }

    private static function number(int $number): string
    {
        if ($number >= 1000000) {
            return self::number(intdiv($number, 1000000)).'ล้าน'.($number % 1000000 ? self::number($number % 1000000) : '');
        }

        $digits = array_reverse(str_split((string) $number));
        $text = '';

        foreach ($digits as $pos => $digit) {
            $digit = (int) $digit;
            if ($digit === 0) {
                continue;
            }

            $word = match (true) {
                $pos === 0 && $digit === 1 && $number > 9 => 'เอ็ด',
                $pos === 1 && $digit === 1 => '',
                $pos === 1 && $digit === 2 => 'ยี่',
                default => self::DIGITS[$digit],
            };

            $text = $word.self::UNITS[$pos].$text;
        }

        return $text;
    }
}
