<?php

namespace App\Enums\Platform;

/**
 * The configurable reaction to a subscription reaching its expiration date.
 */
enum ExpirationBehavior: string
{
    case GRACE_PERIOD = 'grace_period';
    case RESTRICTED_ACCESS = 'restricted_access';
    case SUSPENDED = 'suspended';
    case READ_ONLY = 'read_only';

    public function label(): string
    {
        return match ($this) {
            self::GRACE_PERIOD => 'Grace Period',
            self::RESTRICTED_ACCESS => 'Restricted Access',
            self::SUSPENDED => 'Suspend',
            self::READ_ONLY => 'Read Only',
        };
    }

    /**
     * Dropdown options for the admin UI.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toOptions(): array
    {
        return array_map(
            static fn (self $behavior) => ['value' => $behavior->value, 'label' => $behavior->label()],
            self::cases()
        );
    }
}
