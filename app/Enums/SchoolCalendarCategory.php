<?php

namespace App\Enums;

/**
 * The categories a school calendar event can belong to.
 */
enum SchoolCalendarCategory: string
{
    case HOLIDAY = 'holiday';
    case ENROLLMENT = 'enrollment';
    case EXAMINATION = 'examination';
    case SCHOOL_EVENT = 'school-event';
    case ANNOUNCEMENT = 'announcement';
    case SUSPENSION = 'suspension';

    /**
     * Human readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::HOLIDAY => 'Holiday',
            self::ENROLLMENT => 'Enrollment',
            self::EXAMINATION => 'Examination',
            self::SCHOOL_EVENT => 'School Event',
            self::ANNOUNCEMENT => 'Announcement',
            self::SUSPENSION => 'Suspension',
        };
    }

    /**
     * All calendar categories as [value, label].
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toSeedData(): array
    {
        return array_map(
            static fn (self $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ],
            self::cases()
        );
    }
}
