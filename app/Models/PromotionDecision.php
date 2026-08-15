<?php

namespace App\Models;

use Database\Factories\PromotionDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionDecision extends Model
{
    /** @use HasFactory<PromotionDecisionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['student_id', 'enrollment_id', 'academic_year_id', 'grade_level_id', 'decision', 'general_average', 'basis_snapshot', 'override_reason', 'decided_by', 'decided_at'];

    protected function casts(): array
    {
        return ['general_average' => 'decimal:2', 'basis_snapshot' => 'array', 'decided_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
