<?php

namespace App\Repositories;

use App\Models\GradeLevel;
use App\Repositories\Contracts\GradeLevelRepositoryInterface;

class GradeLevelRepository extends BaseRepository implements GradeLevelRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return GradeLevel::class;
    }
}
