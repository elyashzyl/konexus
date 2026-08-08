<?php

namespace App\Repositories;

use App\Models\Subject;
use App\Repositories\Contracts\SubjectRepositoryInterface;

class SubjectRepository extends BaseRepository implements SubjectRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Subject::class;
    }
}
