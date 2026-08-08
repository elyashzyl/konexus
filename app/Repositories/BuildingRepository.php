<?php

namespace App\Repositories;

use App\Models\Building;
use App\Repositories\Contracts\BuildingRepositoryInterface;

class BuildingRepository extends BaseRepository implements BuildingRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Building::class;
    }
}
