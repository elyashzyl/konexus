<?php

namespace App\Models;

use Database\Factories\StudentSubjectEnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentSubjectEnrollment extends Model
{
    /** @use HasFactory<StudentSubjectEnrollmentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['enrollment_id', 'student_id', 'curriculum_program_id', 'curriculum_entry_id', 'subject_offering_id', 'status', 'subject_snapshot', 'assessment_policy_snapshot'];

    protected function casts(): array
    {
        return ['subject_snapshot' => 'array', 'assessment_policy_snapshot' => 'array'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function curriculumProgram(): BelongsTo
    {
        return $this->belongsTo(CurriculumProgram::class);
    }

    public function curriculumEntry(): BelongsTo
    {
        return $this->belongsTo(CurriculumEntry::class);
    }

    public function subjectOffering(): BelongsTo
    {
        return $this->belongsTo(SubjectOffering::class);
    }
}
