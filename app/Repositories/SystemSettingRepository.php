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
}
