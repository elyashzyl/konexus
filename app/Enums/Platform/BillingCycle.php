<?php

namespace App\Enums\Platform;

/**
 * The billing cadence of a subscription plan.
 */
enum BillingCycle: string
{
    case MONTHLY = 'monthly';
    case ANNUAL = 'annual';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly',
            self::ANNUAL => 'Annual',
            self::CUSTOM => 'Custom',
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
            static fn (self $cycle) => ['value' => $cycle->value, 'label' => $cycle->label()],
            self::cases()
        );
    }
}
