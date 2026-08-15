<?php

namespace App\Enums\Platform;

/**
 * The lifecycle state of a subscription invoice.
 */
enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }

    /**
     * Whether payments may still be recorded against this invoice.
     */
    public function isPayable(): bool
    {
        return in_array($this, [self::PENDING, self::PARTIALLY_PAID, self::OVERDUE], true);
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
