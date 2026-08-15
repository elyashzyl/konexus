<?php

namespace App\Enums\Platform;

/**
 * The lifecycle state of a tenant subscription.
 */
enum SubscriptionStatus: string
{
    case TRIAL = 'trial';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case GRACE_PERIOD = 'grace_period';
    case SUSPENDED = 'suspended';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';

    /**
     * Human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::TRIAL => 'Trial',
            self::ACTIVE => 'Active',
            self::PAST_DUE => 'Past Due',
            self::GRACE_PERIOD => 'Grace Period',
            self::SUSPENDED => 'Suspended',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
            self::PENDING => 'Pending',
        };
    }

    /**
     * Whether the status permits normal (non-restricted) usage.
     */
    public function allowsAccess(): bool
    {
        return in_array($this, [self::TRIAL, self::ACTIVE, self::GRACE_PERIOD], true);
    }

    /**
     * Statuses considered payable / collectable by billing.
     */
    public function isBillable(): bool
    {
        return in_array($this, [self::TRIAL, self::ACTIVE, self::PAST_DUE, self::GRACE_PERIOD], true);
    }

    /**
     * Dropdown options for the admin UI.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toOptions(): array
    {
        return array_map(
            static fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }
}
