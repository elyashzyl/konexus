<?php

namespace App\Repositories;

use App\Models\ParentGuardian;
use App\Repositories\Contracts\ParentRepositoryInterface;

class ParentRepository extends BaseRepository implements ParentRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return ParentGuardian::class;
    }
}
