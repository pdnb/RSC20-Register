<?php

namespace App\Enums;

/** What a staff member or student registers for. Executives always attend the conference. */
enum Attendance: string
{
    case Conference = 'conference';
    case BoardGame = 'board_game';
    case ConferenceAndBoardGame = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Conference => 'เข้าร่วมประชุม',
            self::BoardGame => 'แข่งขันบอร์ดเกม',
            self::ConferenceAndBoardGame => 'เข้าร่วมประชุม + แข่งขันบอร์ดเกม',
        };
    }

    /** Short label for badges and tables. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Conference => 'ประชุม',
            self::BoardGame => 'บอร์ดเกม',
            self::ConferenceAndBoardGame => 'ประชุม + บอร์ดเกม',
        };
    }

    /** What the option includes, shown under it in the registration form. */
    public function description(): string
    {
        return match ($this) {
            self::Conference => 'มีค่าลงทะเบียน',
            self::BoardGame => 'ไม่มีค่าลงทะเบียน',
            self::ConferenceAndBoardGame => 'มีค่าลงทะเบียน · ลงทีมบอร์ดเกม',
        };
    }

    /** Attending the conference brings the registration fee and a shirt. Everyone gets food. */
    public function includesConference(): bool
    {
        return $this !== self::BoardGame;
    }

    public function includesBoardGame(): bool
    {
        return $this !== self::Conference;
    }
}
