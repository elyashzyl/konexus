<?php

namespace App\Repositories;

use App\Models\GradeCorrection;
use App\Repositories\Contracts\GradeCorrectionRepositoryInterface;

class GradeCorrectionRepository extends BaseRepository implements GradeCorrectionRepositoryInterface
{
    protected function model(): string
    {
        return GradeCorrection::class;
    }
}
