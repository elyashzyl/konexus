<?php

namespace App\Enums;

/**
 * The status of a single requirement attached to an enrollment.
 */
enum RequirementItemStatus: string
{
    case NOT_SUBMITTED = 'not-submitted';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under-review';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case NOT_REQUIRED = 'not-required';

    public function label(): string
    {
        return match ($this) {
            self::NOT_SUBMITTED => 'Not Submitted',
            self::SUBMITTED => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::NOT_REQUIRED => 'Not Required',
        };
    }

    /**
     * Statuses that satisfy a requirement.
     *
     * @return list<string>
     */
    public static function satisfiedStatuses(): array
    {
        return [self::VERIFIED->value, self::NOT_REQUIRED->value];
    }
}