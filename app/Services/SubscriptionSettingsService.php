<?php

namespace App\Services;

use App\Models\SchoolProfile;
use App\Models\SubscriptionSetting;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SubscriptionSettingRepositoryInterface;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\Builder;

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
    public function get(string $key, mixed $default = null, ?int $schoolProfileId = null): mixed
    {
        $schoolProfileId = $this->resolveSchoolId($schoolProfileId);

        $setting = $this->settingQuery($key, $schoolProfileId)->first();

        return $setting ? $setting->typedValue() : $default;
    }

    /**
     * Set a setting value, inferring its type from the known defaults.
     */
    public function set(string $key, mixed $value, ?string $type = null, ?string $group = null, ?int $schoolProfileId = null): SubscriptionSetting
    {
        $schoolProfileId = $this->resolveSchoolId($schoolProfileId);
        $known = self::DEFAULTS[$key] ?? null;
        $type ??= $known['type'] ?? $this->inferType($value);

        $setting = $this->settingQuery($key, $schoolProfileId)->first();

        if ($setting === null) {
            $setting = new SubscriptionSetting(['key' => $key, 'school_profile_id' => $schoolProfileId]);
        }

        $setting->fill([
            'value' => SubscriptionSetting::encode($type, $value),
            'type' => $type,
            'group' => $group ?? $known['group'] ?? 'general',
            'description' => $known['description'] ?? null,
        ])->save();

        return $setting;
    }

    /**
     * Every active setting grouped by group for a school.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function grouped(?int $schoolProfileId = null): array
    {
        $schoolProfileId = $this->resolveSchoolId($schoolProfileId);

        return SubscriptionSetting::query()
            ->when($schoolProfileId === null, fn ($query) => $query->whereNull('school_profile_id'))
            ->when($schoolProfileId !== null, fn ($query) => $query->where('school_profile_id', $schoolProfileId))
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
     * Persist a batch of settings (key => value) for a school.
     *
     * @param  array<string, mixed>  $values
     */
    public function bulkSet(array $values, ?int $schoolProfileId = null): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, null, null, $schoolProfileId);
        }
    }

    /**
     * Seed the default settings (idempotent) for a school.
     */
    public function seedDefaults(?int $schoolProfileId = null): void
    {
        foreach (self::DEFAULTS as $key => $definition) {
            $this->set($key, $definition['value'], $definition['type'], $definition['group'], $schoolProfileId);
        }
    }

    /**
     * The query scoped to a setting key and (possibly null) school.
     *
     * @return Builder<SubscriptionSetting>
     */
    protected function settingQuery(string $key, ?int $schoolProfileId)
    {
        return SubscriptionSetting::query()
            ->where('key', $key)
            ->when($schoolProfileId === null, fn ($query) => $query->whereNull('school_profile_id'))
            ->when($schoolProfileId !== null, fn ($query) => $query->where('school_profile_id', $schoolProfileId));
    }

    /**
     * Resolve the school a setting read/write applies to.
     *
     * When none is provided the currently authenticated user's school is used;
     * unauthenticated contexts (scheduled jobs, seeding) fall back to the first
     * active school. Null is returned when no school exists at all so legacy
     * unassigned rows remain reachable.
     */
    protected function resolveSchoolId(?int $schoolProfileId): ?int
    {
        if ($schoolProfileId !== null) {
            return $schoolProfileId;
        }

        $user = SchoolContext::user();

        if ($user !== null && $user->school_profile_id !== null) {
            return (int) $user->school_profile_id;
        }

        $fallback = SchoolProfile::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $fallback !== null ? (int) $fallback : null;
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
