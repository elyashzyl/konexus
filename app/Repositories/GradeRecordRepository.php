<?php

namespace App\Repositories;

use App\Models\GradeRecord;
use App\Repositories\Contracts\GradeRecordRepositoryInterface;

class GradeRecordRepository extends BaseRepository implements GradeRecordRepositoryInterface
{
    protected function model(): string
    {
        return GradeRecord::class;
    }
}
