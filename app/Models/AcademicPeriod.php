<?php

namespace App\Models;

use Database\Factories\AcademicPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicPeriod extends Model
{
    /** @use HasFactory<AcademicPeriodFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['curriculum_program_id', 'name', 'code', 'sequence', 'start_date', 'end_date', 'status', 'is_active'];

    protected function casts(): array
    {
        return ['sequence' => 'integer', 'start_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean'];
    }

    public function curriculumProgram(): BelongsTo
    {
        return $this->belongsTo(CurriculumProgram::class);
    }
}
