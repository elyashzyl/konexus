<?php

namespace App\Enums;

/**
 * Configurable subject types used by the curriculum.
 *
 * The canonical list a school may configure lives in the `subject-type` master
 * data list. This enum mirrors the seeded defaults so logic can reference
 * stable machine codes.
 */
enum SubjectType: string
{
    case CORE = 'core';
    case APPLIED = 'applied';
    case SPECIALIZED = 'specialized';
    case ELECTIVE = 'elective';
    case ACADEMIC_ELECTIVE = 'academic-elective';
    case TECHPRO_ELECTIVE = 'techpro-elective';
    case OTHER = 'other';

    /**
     * Human readable label for the subject type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CORE => 'Core',
            self::APPLIED => 'Applied',
            self::SPECIALIZED => 'Specialized',
            self::ELECTIVE => 'Elective',
            self::ACADEMIC_ELECTIVE => 'Academic Elective',
            self::TECHPRO_ELECTIVE => 'Technical-Professional Elective',
            self::OTHER => 'Other',
        };
    }

    /**
     * All types as [value, label] pairs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toOptions(): array
    {
        return array_map(
            static fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}
