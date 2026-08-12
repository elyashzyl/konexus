<?php

namespace App\Repositories;

use App\Models\Guardian;
use App\Repositories\Contracts\GuardianRepositoryInterface;

class GuardianRepository extends BaseRepository implements GuardianRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Guardian::class;
    }
}
