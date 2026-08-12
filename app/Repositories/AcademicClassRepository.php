<?php

namespace App\Repositories;

use App\Models\AcademicClass;
use App\Repositories\Contracts\AcademicClassRepositoryInterface;

class AcademicClassRepository extends BaseRepository implements AcademicClassRepositoryInterface
{
    protected function model(): string
    {
        return AcademicClass::class;
    }
}
