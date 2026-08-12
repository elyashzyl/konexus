<?php

namespace App\Enums;

/**
 * The lifecycle statuses of a student document.
 */
enum StudentDocumentStatus: string
{
    case NOT_SUBMITTED = 'not-submitted';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under-review';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case NOT_REQUIRED = 'not-required';

    /**
     * Human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::NOT_SUBMITTED => 'Not Submitted',
            self::SUBMITTED => 'Submitted',
            self::UNDER_REVIEW => 'Under Review',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
            self::NOT_REQUIRED => 'Not Required',
        };
    }

    /**
     * Statuses that indicate a usable, acceptable document.
     *
     * @return list<string>
     */
    public static function acceptedStatuses(): array
    {
        return [self::VERIFIED->value, self::NOT_REQUIRED->value];
    }

    /**
     * The valid statuses a user may transition a document to.
     *
     * @return list<string>
     */
    public static function reviewStatuses(): array
    {
        return [self::UNDER_REVIEW->value, self::VERIFIED->value, self::REJECTED->value];
    }

    /**
     * All statuses as [value, label] pairs.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toOptions(): array
    {
        return array_map(
            static fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}