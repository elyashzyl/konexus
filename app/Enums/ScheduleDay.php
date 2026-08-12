<?php

namespace App\Enums;

/**
 * The default school operating days.
 *
 * The canonical list a school may configure lives in the academic settings;
 * this enum mirrors the seeded defaults used by the schedule module.
 */
enum ScheduleDay: string
{
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';

    /**
     * Human readable label for the day.
     */
    public function label(): string
    {
        return match ($this) {
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
        };
    }

    /**
     * The short label of the day (e.g. Mon).
     */
    public function shortLabel(): string
    {
        return substr($this->label(), 0, 3);
    }

    /**
     * All days as [value, label] pairs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toOptions(): array
    {
        return array_map(
            static fn (self $day) => [
                'value' => $day->value,
                'label' => $day->label(),
            ],
            self::cases()
        );
    }
}