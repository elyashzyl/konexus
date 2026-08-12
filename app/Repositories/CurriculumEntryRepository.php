<?php

namespace App\Repositories;

use App\Models\CurriculumEntry;
use App\Repositories\Contracts\CurriculumEntryRepositoryInterface;

class CurriculumEntryRepository extends BaseRepository implements CurriculumEntryRepositoryInterface
{
    protected function model(): string
    {
        return CurriculumEntry::class;
    }
}
