<?php

namespace App\Support;

use App\Models\Campus;

class CampusContext
{
    private static ?Campus $campus = null;

    public static function set(?Campus $campus): void
    {
        self::$campus = $campus;
    }

    public static function campus(): ?Campus
    {
        return self::$campus;
    }

    public static function id(): ?int
    {
        return self::$campus?->getKey();
    }

    public static function clear(): void
    {
        self::$campus = null;
    }
}
