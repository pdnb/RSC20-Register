<?php

namespace App\Support;

/** The two signed name-badge backgrounds. The PDF picks one per card. */
final class BadgeBackgrounds
{
    /** @var array<string, string> kind => filename under resources/images/badges */
    public const FILES = [
        'conference' => 'conference.jpg',
        'board_game' => 'board-game.jpg',
    ];

    public static function path(string $kind): string
    {
        return resource_path('images/badges/'.self::FILES[$kind]);
    }

    public static function dataUri(string $kind): string
    {
        return 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents(self::path($kind)));
    }
}
