<?php

namespace App\Repositories;

use App\Models\EnrollmentRequirement;
use App\Repositories\Contracts\EnrollmentRequirementRepositoryInterface;

class EnrollmentRequirementRepository extends BaseRepository implements EnrollmentRequirementRepositoryInterface
{
    protected function model(): string
    {
        return EnrollmentRequirement::class;
    }
}