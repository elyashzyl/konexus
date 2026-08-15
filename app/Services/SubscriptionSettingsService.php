<?php

namespace App\Services;

use App\Models\SubscriptionSetting;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SubscriptionSettingRepositoryInterface;

/**
 * Typed access to the configurable platform subscription settings. Every value
 * is stored as a row in `subscription_settings` so operators can tune trial
 * periods, grace windows and expiration behavior without code changes.
 */
class SubscriptionSettingsService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['key', 'group', 'description'];

    protected array $sortable = ['id', 'key', 'group', 'created_at', 'updated_at'];

    protected string $defaultSortBy = 'key';

    /**
     * The default settings seeded for the platform.
     *
     * @var array<string, array{value: mixed, type: string, group: string, description: string}>
     */
    public const DEFAULTS = [
        'default_grace_days' => ['value' => 7, 'type' => 'integer', 'group' => 'general', 'description' => 'Days granted after a subscription expires before enforcement.'],
        'default_expiration_behavior' => ['value' => 'grace_period', 'type' => 'string', 'group' => 'general', 'description' => 'Default reaction when a subscription reaches its expiration date.'],
        'default_trial_days' => ['value' => 14, 'type' => 'integer', 'group' => 'general', 'description' => 'Default trial length for new tenants without an explicit plan trial.'],
        'usage_warning_thresholds' => ['value' => [80, 90, 100], 'type' => 'json', 'group' => 'usage', 'description' => 'Percent thresholds that trigger usage warnings.'],
        'currency' => ['value' => 'PHP', 'type' => 'string', 'group' => 'billing', 'description' => 'The currency used on invoices.'],
        'billing_email' => ['value' => null, 'type' => 'string', 'group' => 'billing', 'description' => 'Contact address for billing inquiries.'],
        'expiring_notice_days' => ['value' => 30, 'type' => 'integer', 'group' => 'notifications', 'description' => 'Days before expiration when renewal reminders are sent.'],
    ];

    public function __construct(private readonly SubscriptionSettingRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Read a setting as a typed value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = SubscriptionSetting::query()->where('key', $key)->first();

        return $setting ? $setting->typedValue() : $default;
    }

    /**
     * Set a setting value, inferring its type from the known defaults.
     */
    public function set(string $key, mixed $value, ?string $type = null, ?string $group = null): SubscriptionSetting
    {
        $known = self::DEFAULTS[$key] ?? null;
        $type ??= $known['type'] ?? $this->inferType($value);

        return SubscriptionSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => SubscriptionSetting::encode($type, $value),
                'type' => $type,
                'group' => $group ?? $known['group'] ?? 'general',
                'description' => $known['description'] ?? null,
            ]
        );
    }

    /**
     * Every active setting grouped by group.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function grouped(): array
    {
        return SubscriptionSetting::query()
            ->where('is_active', true)
            ->orderBy('key')
            ->get()
            ->groupBy(fn (SubscriptionSetting $setting) => $setting->group ?: 'general')
            ->map(function ($settings) {
                return $settings->map(fn (SubscriptionSetting $setting) => [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => $setting->typedValue(),
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'description' => $setting->description,
                ])->values();
            })
            ->all();
    }

    /**
     * Persist a batch of settings (key => value).
     *
     * @param  array<string, mixed>  $values
     */
    public function bulkSet(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Seed the default settings (idempotent).
     */
    public function seedDefaults(): void
    {
        foreach (self::DEFAULTS as $key => $definition) {
            $this->set($key, $definition['value'], $definition['type'], $definition['group']);
        }
    }

    /**
     * Infer a storage type from a PHP value.
     */
    protected function inferType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'decimal';
        }

        if (is_array($value)) {
            return 'json';
        }

        return 'string';
    }
}