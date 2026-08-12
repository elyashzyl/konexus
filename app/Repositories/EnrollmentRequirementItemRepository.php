<?php

namespace App\Repositories;

use App\Models\EnrollmentRequirementItem;
use App\Repositories\Contracts\EnrollmentRequirementItemRepositoryInterface;

class EnrollmentRequirementItemRepository extends BaseRepository implements EnrollmentRequirementItemRepositoryInterface
{
    protected function model(): string
    {
        return EnrollmentRequirementItem::class;
    }
}