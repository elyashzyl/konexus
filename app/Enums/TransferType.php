<?php

namespace App\Enums;

/**
 * Where an enrollment transfer leads.
 */
enum TransferType: string
{
    case WITHIN_SCHOOL = 'within-school';
    case WITHIN_BRANCH = 'within-branch';
    case OTHER_BRANCH = 'other-branch';
    case OTHER_SCHOOL = 'other-school';

    public function label(): string
    {
        return match ($this) {
            self::WITHIN_SCHOOL => 'Within School',
            self::WITHIN_BRANCH => 'Within Branch',
            self::OTHER_BRANCH => 'To Another Branch',
            self::OTHER_SCHOOL => 'To Another School',
        };
    }
}