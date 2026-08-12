<?php

namespace App\Enums;

/**
 * The default enrollment types.
 *
 * The canonical list a school may configure lives in the `enrollment-type`
 * master data list; this enum mirrors the seeded defaults providing machine
 * codes used by the assignment and requirement logic.
 */
enum EnrollmentType: string
{
    case NEW_STUDENT = 'new-student';
    case CONTINUING = 'continuing';
    case RETURNING = 'returning';
    case TRANSFEREE = 'transferee';
    case RE_ENROLLEE = 're-enrollee';

    public function label(): string
    {
        return match ($this) {
            self::NEW_STUDENT => 'New Student',
            self::CONTINUING => 'Continuing',
            self::RETURNING => 'Returning',
            self::TRANSFEREE => 'Transferee',
            self::RE_ENROLLEE => 'Re-Enrollee',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type) => $type->value, self::cases());
    }
}