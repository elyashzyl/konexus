<?php

namespace App\Repositories;

use App\Models\EnrollmentCapacityOverride;
use App\Repositories\Contracts\EnrollmentCapacityOverrideRepositoryInterface;

class EnrollmentCapacityOverrideRepository extends BaseRepository implements EnrollmentCapacityOverrideRepositoryInterface
{
    protected function model(): string
    {
        return EnrollmentCapacityOverride::class;
    }
}