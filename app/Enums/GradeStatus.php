<?php

namespace App\Enums;

/**
 * The lifecycle statuses of an academic grade record.
 *
 * The workflow is configurable; these machine codes mirror the seeded defaults
 * used by the approval/publishing flow.
 */
enum GradeStatus: string
{
    case DRAFT = 'draft';
    case IN_PROGRESS = 'in-progress';
    case SUBMITTED = 'submitted';
    case FOR_REVIEW = 'for-review';
    case APPROVED = 'approved';
    case PUBLISHED = 'published';
    case RETURNED = 'returned';
    case CORRECTED = 'corrected';

    /**
     * Statuses that are editable by the assigned teacher.
     *
     * @return list<string>
     */
    public static function editableStatuses(): array
    {
        return [self::DRAFT->value, self::IN_PROGRESS->value, self::RETURNED->value];
    }

    /**
     * Statuses that represent a locked, finalized grade.
     *
     * @return list<string>
     */
    public static function finalizedStatuses(): array
    {
        return [self::APPROVED->value, self::PUBLISHED->value];
    }

    /**
     * Human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_PROGRESS => 'In Progress',
            self::SUBMITTED => 'Submitted',
            self::FOR_REVIEW => 'For Review',
            self::APPROVED => 'Approved',
            self::PUBLISHED => 'Published',
            self::RETURNED => 'Returned',
            self::CORRECTED => 'Corrected',
        };
    }

    /**
     * All statuses as [value, label] pairs for dropdowns.
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