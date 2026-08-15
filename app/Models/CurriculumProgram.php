<?php

namespace App\Models;

use Database\Factories\CurriculumProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumProgram extends Model
{
    /** @use HasFactory<CurriculumProgramFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['academic_year_id', 'name', 'code', 'framework', 'calendar_type', 'grade_level_ids', 'clusters', 'compliance_status', 'status', 'local_adaptation_reason', 'approved_by', 'approved_at', 'is_active'];

    protected function casts(): array
    {
        return ['grade_level_ids' => 'array', 'clusters' => 'array', 'approved_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AcademicPeriod::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(CurriculumEntry::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function includesGradeLevel(int $gradeLevelId): bool
    {
        return in_array($gradeLevelId, $this->grade_level_ids ?? [], true);
    }
}
