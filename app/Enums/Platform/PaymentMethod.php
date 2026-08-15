<?php

namespace App\Enums\Platform;

/**
 * The channel used to pay a subscription invoice.
 */
enum PaymentMethod: string
{
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case GCASH = 'gcash';
    case PAYMAYA = 'paymaya';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CASH => 'Cash',
            self::GCASH => 'GCash',
            self::PAYMAYA => 'PayMaya',
            self::OTHER => 'Other',
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
            static fn (self $method) => ['value' => $method->value, 'label' => $method->label()],
            self::cases()
        );
    }
}
