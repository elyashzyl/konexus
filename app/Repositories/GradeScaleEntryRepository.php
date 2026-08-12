<?php

namespace App\Repositories;

use App\Models\GradeScaleEntry;
use App\Repositories\Contracts\GradeScaleEntryRepositoryInterface;

class GradeScaleEntryRepository extends BaseRepository implements GradeScaleEntryRepositoryInterface
{
    protected function model(): string
    {
        return GradeScaleEntry::class;
    }
}
