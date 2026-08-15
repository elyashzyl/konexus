<?php

namespace App\Models;

use Database\Factories\AssessmentScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentScore extends Model
{
    /** @use HasFactory<AssessmentScoreFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['assessment_item_id', 'student_subject_enrollment_id', 'score', 'recorded_by'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function assessmentItem(): BelongsTo
    {
        return $this->belongsTo(AssessmentItem::class);
    }

    public function studentSubjectEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentSubjectEnrollment::class);
    }
}
