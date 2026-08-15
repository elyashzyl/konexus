<?php

namespace App\Repositories;

use App\Models\Tuition;
use App\Repositories\Contracts\TuitionRepositoryInterface;

class TuitionRepository extends BaseRepository implements TuitionRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Tuition::class;
    }
}