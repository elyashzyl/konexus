<?php

namespace App\Models;

use App\Enums\GradeStatus;
use Database\Factories\GradeRecordFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class GradeRecord extends Model
{
    /** @use HasFactory<GradeRecordFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'academic_term_id',
        'academic_period_id',
        'student_subject_enrollment_id',
        'grade_level_id',
        'section_id',
        'subject_id',
        'subject_offering_id',
        'teacher_id',
        'raw_grade',
        'final_grade',
        'remarks',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'published_at',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_grade' => 'decimal:2',
            'final_grade' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function studentSubjectEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentSubjectEnrollment::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subjectOffering(): BelongsTo
    {
        return $this->belongsTo(SubjectOffering::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<GradeCorrection, $this>
     */
    public function corrections(): HasMany
    {
        return $this->hasMany(GradeCorrection::class);
    }

    /**
     * Whether the record is in the editable draft state.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, GradeStatus::editableStatuses(), true);
    }

    protected function displayStatusLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => GradeStatus::tryFrom($this->status)?->label() ?? ucfirst($this->status)
        );
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly(['raw_grade', 'final_grade', 'remarks', 'status'])
            ->useLogName('grade_records');
    }
}
