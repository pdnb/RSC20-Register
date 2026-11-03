<?php

namespace Tests\Unit;

use App\Support\ThaiBaht;
use App\Support\ThaiId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ThaiHelpersTest extends TestCase
{
    public function test_thai_id_checksum(): void
    {
        $this->assertTrue(ThaiId::isValid('1101700203450'));
        $this->assertTrue(ThaiId::isValid('1-1017-00203-45-0'));
        $this->assertFalse(ThaiId::isValid('1101700203451'));
        $this->assertFalse(ThaiId::isValid('123'));
        $this->assertSame('x-xxxx-xxxxx-45-0', ThaiId::mask('1101700203450'));
        $this->assertSame('1-1017-00203-45-0', ThaiId::format('1101700203450'));
    }

    #[DataProvider('bahtCases')]
    public function test_baht_text(float $amount, string $expected): void
    {
        $this->assertSame($expected, ThaiBaht::text($amount));
    }

    public static function bahtCases(): array
    {
        return [
            [0, 'ศูนย์บาทถ้วน'],
            [1, 'หนึ่งบาทถ้วน'],
            [11, 'สิบเอ็ดบาทถ้วน'],
            [21, 'ยี่สิบเอ็ดบาทถ้วน'],
            [101, 'หนึ่งร้อยเอ็ดบาทถ้วน'],
            [1500, 'หนึ่งพันห้าร้อยบาทถ้วน'],
            [21501.25, 'สองหมื่นหนึ่งพันห้าร้อยเอ็ดบาทยี่สิบห้าสตางค์'],
            [1000000, 'หนึ่งล้านบาทถ้วน'],
            [2100000, 'สองล้านหนึ่งแสนบาทถ้วน'],
        ];
    }
}
