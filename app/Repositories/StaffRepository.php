<?php

namespace App\Repositories;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;

class StaffRepository extends BaseRepository implements StaffRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Staff::class;
    }
}
