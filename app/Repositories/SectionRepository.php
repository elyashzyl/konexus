<?php

namespace App\Repositories;

use App\Models\Section;
use App\Repositories\Contracts\SectionRepositoryInterface;

class SectionRepository extends BaseRepository implements SectionRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return Section::class;
    }
}
