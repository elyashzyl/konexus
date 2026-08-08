<?php

namespace App\Repositories;

use App\Models\SchoolProfile;
use App\Repositories\Contracts\SchoolProfileRepositoryInterface;

class SchoolProfileRepository extends BaseRepository implements SchoolProfileRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return SchoolProfile::class;
    }
}
