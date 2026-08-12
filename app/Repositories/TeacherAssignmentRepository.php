<?php

namespace App\Repositories;

use App\Models\TeacherAssignment;
use App\Repositories\Contracts\TeacherAssignmentRepositoryInterface;

class TeacherAssignmentRepository extends BaseRepository implements TeacherAssignmentRepositoryInterface
{
    protected function model(): string
    {
        return TeacherAssignment::class;
    }
}
