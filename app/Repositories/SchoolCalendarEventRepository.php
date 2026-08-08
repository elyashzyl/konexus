<?php

namespace App\Repositories;

use App\Models\SchoolCalendarEvent;
use App\Repositories\Contracts\SchoolCalendarEventRepositoryInterface;

class SchoolCalendarEventRepository extends BaseRepository implements SchoolCalendarEventRepositoryInterface
{
    /**
     * The model handled by this repository.
     */
    protected function model(): string
    {
        return SchoolCalendarEvent::class;
    }
}
