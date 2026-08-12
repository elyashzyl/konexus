<?php

namespace App\Repositories;

use App\Models\Teacher;
use App\Repositories\Contracts\TeacherRepositoryInterface;

class TeacherRepository extends BaseRepository implements TeacherRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Teacher::class;
    }
}
