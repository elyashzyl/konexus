<?php

namespace App\Repositories;

use App\Models\AcademicClassStudent;
use App\Repositories\Contracts\AcademicClassStudentRepositoryInterface;

class AcademicClassStudentRepository extends BaseRepository implements AcademicClassStudentRepositoryInterface
{
    protected function model(): string
    {
        return AcademicClassStudent::class;
    }
}
