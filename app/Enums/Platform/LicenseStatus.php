<?php

namespace App\Enums\Platform;

/**
 * The lifecycle state of a tenant license key.
 */
enum LicenseStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::REVOKED => 'Revoked',
            self::SUSPENDED => 'Suspended',
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
            static fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }
}
