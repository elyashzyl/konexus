<?php

namespace App\Repositories;

use App\Models\SubjectOffering;
use App\Repositories\Contracts\SubjectOfferingRepositoryInterface;

class SubjectOfferingRepository extends BaseRepository implements SubjectOfferingRepositoryInterface
{
    protected function model(): string
    {
        return SubjectOffering::class;
    }
}
