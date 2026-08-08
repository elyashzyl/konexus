<?php

namespace App\Enums;

/**
 * The basic education levels used to group grade levels.
 */
enum EducationLevel: string
{
    case PRIMARY = 'primary';
    case JUNIOR_HIGH = 'junior-high';
    case SENIOR_HIGH = 'senior-high';
    case INTEGRATED = 'integrated';

    /**
     * Human readable label for the education level.
     */
    public function label(): string
    {
        return match ($this) {
            self::PRIMARY => 'Primary',
            self::JUNIOR_HIGH => 'Junior High School',
            self::SENIOR_HIGH => 'Senior High School',
            self::INTEGRATED => 'Integrated',
        };
    }

    /**
     * All education levels as [value, label].
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toSeedData(): array
    {
        return array_map(
            static fn (self $level) => [
                'value' => $level->value,
                'label' => $level->label(),
            ],
            self::cases()
        );
    }
}
