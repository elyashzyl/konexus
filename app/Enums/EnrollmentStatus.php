<?php

namespace App\Enums;

/**
 * The default enrollment workflow statuses.
 *
 * The canonical list a school may configure lives in the `enrollment-status`
 * master data list. This enum mirrors the seeded defaults so workflow logic
 * (transitions, stamps) can reference stable machine codes.
 */
enum EnrollmentStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case FOR_VERIFICATION = 'for-verification';
    case REQUIREMENTS_INCOMPLETE = 'requirements-incomplete';
    case VERIFIED = 'verified';
    case FOR_APPROVAL = 'for-approval';
    case APPROVED = 'approved';
    case OFFICIALLY_ENROLLED = 'officially-enrolled';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case WITHDRAWN = 'withdrawn';
    case TRANSFERRED = 'transferred';

    /**
     * Statuses that represent an enrollment which is still active/in-progress.
     *
     * @return list<string>
     */
    public static function activeStatuses(): array
    {
        return [
            self::DRAFT->value,
            self::PENDING->value,
            self::FOR_VERIFICATION->value,
            self::REQUIREMENTS_INCOMPLETE->value,
            self::VERIFIED->value,
            self::FOR_APPROVAL->value,
            self::APPROVED->value,
            self::OFFICIALLY_ENROLLED->value,
        ];
    }

    /**
     * Statuses that count toward section occupancy.
     *
     * @return list<string>
     */
    public static function occupancyStatuses(): array
    {
        return [self::APPROVED->value, self::OFFICIALLY_ENROLLED->value];
    }

    /**
     * Terminal statuses — no further workflow actions are permitted.
     *
     * @return list<string>
     */
    public static function terminalStatuses(): array
    {
        return [
            self::REJECTED->value,
            self::CANCELLED->value,
            self::WITHDRAWN->value,
            self::TRANSFERRED->value,
        ];
    }

    /**
     * Human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::FOR_VERIFICATION => 'For Verification',
            self::REQUIREMENTS_INCOMPLETE => 'Requirements Incomplete',
            self::VERIFIED => 'Verified',
            self::FOR_APPROVAL => 'For Approval',
            self::APPROVED => 'Approved',
            self::OFFICIALLY_ENROLLED => 'Officially Enrolled',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
            self::WITHDRAWN => 'Withdrawn',
            self::TRANSFERRED => 'Transferred',
        };
    }
}