<?php

namespace App\Repositories;

use App\Models\AcademicSetting;
use App\Repositories\Contracts\AcademicSettingRepositoryInterface;

class AcademicSettingRepository extends BaseRepository implements AcademicSettingRepositoryInterface
{
    protected function model(): string
    {
        return AcademicSetting::class;
    }
}
