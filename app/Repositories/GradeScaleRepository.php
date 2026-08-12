<?php

namespace App\Repositories;

use App\Models\GradeScale;
use App\Repositories\Contracts\GradeScaleRepositoryInterface;

class GradeScaleRepository extends BaseRepository implements GradeScaleRepositoryInterface
{
    protected function model(): string
    {
        return GradeScale::class;
    }
}
