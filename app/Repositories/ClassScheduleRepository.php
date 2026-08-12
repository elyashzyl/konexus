<?php

namespace App\Repositories;

use App\Models\ClassSchedule;
use App\Repositories\Contracts\ClassScheduleRepositoryInterface;

class ClassScheduleRepository extends BaseRepository implements ClassScheduleRepositoryInterface
{
    protected function model(): string
    {
        return ClassSchedule::class;
    }
}
