<?php

namespace App\Repositories;

use App\Models\Campus;
use App\Repositories\Contracts\CampusRepositoryInterface;

class CampusRepository extends BaseRepository implements CampusRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Campus::class;
    }
}
