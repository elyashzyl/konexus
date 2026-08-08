<?php

namespace App\Repositories;

use App\Models\AcademicYear;
use App\Repositories\Contracts\AcademicYearRepositoryInterface;

class AcademicYearRepository extends BaseRepository implements AcademicYearRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return AcademicYear::class;
    }
}
