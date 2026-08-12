<?php

namespace App\Services;

use App\Models\AcademicSetting;
use App\Repositories\Contracts\AcademicSettingRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Academic settings configure operational behavior of the Academic module
 * (operating days, class-sync toggles, teacher load limit). Values are
 * stored with a type so they can be serialized/deserialized consistently.
 */
class AcademicSettingService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['key', 'group'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'key', 'group', 'sort_order'];

    protected string $defaultSortBy = 'sort_order';

    protected string $defaultSortDir = 'asc';

    public function __construct(private readonly AcademicSettingRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create a setting after normalizing its typed value.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['value'] = $this->encodeValue($data['value'] ?? null, $data['type'] ?? 'string');

        return parent::create($data);
    }

    /**
     * Update a setting, normalizing the typed value.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(\Illuminate\Database\Eloquent\Model $model, array $data): \Illuminate\Database\Eloquent\Model
    {
        $type = $data['type'] ?? $model->type ?? 'string';

        if (array_key_exists('value', $data)) {
            $data['value'] = $this->encodeValue($data['value'], $type);
        }

        return parent::update($model, $data);
    }

    /**
     * The settings grouped for the UI.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function grouped(): array
    {
        return $this->repo->query()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group')
            ->map(fn (Collection $items) => $items->map(fn (AcademicSetting $setting) => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $this->decodeValue($setting),
                'type' => $setting->type,
                'sort_order' => $setting->sort_order,
                'is_active' => $setting->is_active,
            ])->values())
            ->all();
    }

    /**
     * Store a single value by key (create-or-update).
     */
    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): AcademicSetting
    {
        $encoded = $this->encodeValue($value, $type);

        /** @var AcademicSetting $setting */
        $setting = $this->repo->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'type' => $type,
                'value' => $encoded,
                'is_active' => true,
            ]
        );

        return $setting;
    }

    /**
     * Read a single value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->repo->findBy(['key' => $key]);

        return $setting === null ? $default : $this->decodeValue($setting);
    }

    /**
     * Encode a value for storage according to its type.
     */
    protected function encodeValue(mixed $value, string $type): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($type === 'json') {
            return json_encode($value);
        }

        if ($type === 'boolean') {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Decode a stored value according to the setting's type.
     */
    protected function decodeValue(AcademicSetting $setting): mixed
    {
        if ($setting->value === null || $setting->value === '') {
            return null;
        }

        return match ($setting->type) {
            'json' => json_decode($setting->value, true),
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOL),
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            default => $setting->value,
        };
    }
}