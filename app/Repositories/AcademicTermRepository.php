<?php

namespace App\Repositories;

use App\Models\AcademicTerm;
use App\Repositories\Contracts\AcademicTermRepositoryInterface;

class AcademicTermRepository extends BaseRepository implements AcademicTermRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return AcademicTerm::class;
    }
}
