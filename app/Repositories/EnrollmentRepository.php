<?php

namespace App\Repositories;

use App\Models\Enrollment;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;

class EnrollmentRepository extends BaseRepository implements EnrollmentRepositoryInterface
{
    protected function model(): string
    {
        return Enrollment::class;
    }
}