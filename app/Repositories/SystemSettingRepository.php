<?php

namespace App\Repositories;

use App\Models\SystemSetting;
use App\Repositories\Contracts\SystemSettingRepositoryInterface;

class SystemSettingRepository extends BaseRepository implements SystemSettingRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return SystemSetting::class;
    }

    /**
     * Retrieve the raw value of a setting by key, or the default.
     */
    public function value(string $key, mixed $default = null): mixed
    {
        return $this->query()->where('key', $key)->value('value') ?? $default;
    }
}
