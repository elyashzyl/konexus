<?php

namespace App\Enums;

/**
 * The academic calendar structures a school can follow.
 *
 * The number of academic terms within an academic year must match the configured
 * type, unless the type is Custom (which allows an arbitrary number of terms).
 */
enum AcademicCalendarType: string
{
    case THREE_TERM = 'three-term';
    case QUARTERLY = 'quarterly';
    case SEMESTER = 'semester';
    case TRIMESTER = 'trimester';
    case CUSTOM = 'custom';

    /**
     * Human readable label for the calendar type.
     */
    public function label(): string
    {
        return match ($this) {
            self::THREE_TERM => 'Three-Term',
            self::QUARTERLY => 'Quarterly',
            self::SEMESTER => 'Semester',
            self::TRIMESTER => 'Trimester',
            self::CUSTOM => 'Custom',
        };
    }

    /**
     * The expected number of academic terms for this calendar type.
     * Returns null when the type is Custom and any count is allowed.
     */
    public function expectedTermCount(): ?int
    {
        return match ($this) {
            self::THREE_TERM => 3,
            self::QUARTERLY => 4,
            self::SEMESTER => 2,
            self::TRIMESTER => 3,
            self::CUSTOM => null,
        };
    }

    /**
     * All calendar types as [value, label, expected_terms].
     *
     * @return list<array{value: string, label: string, expected_terms: int|null}>
     */
    public static function toSeedData(): array
    {
        return array_map(
            static fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'expected_terms' => $type->expectedTermCount(),
            ],
            self::cases()
        );
    }
}
