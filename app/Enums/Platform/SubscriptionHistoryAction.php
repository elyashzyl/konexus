<?php

namespace App\Enums\Platform;

/**
 * The auditable events recorded against a tenant/subscription.
 */
enum SubscriptionHistoryAction: string
{
    case CREATED = 'created';
    case PLAN_CHANGED = 'plan_changed';
    case RENEWED = 'renewed';
    case SUSPENDED = 'suspended';
    case RESUMED = 'resumed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case TRIAL_STARTED = 'trial_started';
    case TRIAL_CONVERTED = 'trial_converted';
    case INVOICE_CREATED = 'invoice_created';
    case PAYMENT_RECORDED = 'payment_recorded';
    case INVOICE_PAID = 'invoice_paid';
    case FEATURE_ENABLED = 'feature_enabled';
    case FEATURE_DISABLED = 'feature_disabled';
    case LICENSE_CREATED = 'license_created';
    case LICENSE_REGENERATED = 'license_regenerated';
    case TENANT_UPDATED = 'tenant_updated';
    case MANUAL_GRANT = 'manual_grant';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Created',
            self::PLAN_CHANGED => 'Plan Changed',
            self::RENEWED => 'Renewed',
            self::SUSPENDED => 'Suspended',
            self::RESUMED => 'Resumed',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
            self::TRIAL_STARTED => 'Trial Started',
            self::TRIAL_CONVERTED => 'Trial Converted',
            self::INVOICE_CREATED => 'Invoice Created',
            self::PAYMENT_RECORDED => 'Payment Recorded',
            self::INVOICE_PAID => 'Invoice Paid',
            self::FEATURE_ENABLED => 'Feature Enabled',
            self::FEATURE_DISABLED => 'Feature Disabled',
            self::LICENSE_CREATED => 'License Created',
            self::LICENSE_REGENERATED => 'License Regenerated',
            self::TENANT_UPDATED => 'Tenant Updated',
            self::MANUAL_GRANT => 'Manual Grant',
        };
    }

    /**
     * Dropdown options for the audit filter.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toOptions(): array
    {
        return array_map(
            static fn (self $action) => ['value' => $action->value, 'label' => $action->label()],
            self::cases()
        );
    }
}
