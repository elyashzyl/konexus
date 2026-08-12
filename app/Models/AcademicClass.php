<?php

namespace App\Models;

use Database\Factories\AcademicClassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AcademicClass extends Model
{
    /** @use HasFactory<AcademicClassFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'academic_year_id',
        'academic_term_id',
        'campus_id',
        'grade_level_id',
        'section_id',
        'adviser_teacher_id',
        'name',
        'status',
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
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'adviser_teacher_id');
    }

    /**
     * @return HasMany<AcademicClassStudent, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(AcademicClassStudent::class);
    }

    /**
     * The active students of this class.
     *
     * @return HasMany<AcademicClassStudent, $this>
     */
    public function activeMembers(): HasMany
    {
        return $this->members()->where('is_active', true);
    }

    /**
     * @return HasManyThrough<Student, AcademicClassStudent, $this>
     */
    public function students(): HasManyThrough
    {
        return $this->hasManyThrough(
            Student::class,
            AcademicClassStudent::class,
            'academic_class_id',
            'id',
            'id',
            'student_id'
        );
    }

    /**
     * The display name of the class.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name
            ?: implode(' · ', array_filter([
                $this->gradeLevel?->name,
                $this->section?->name,
                $this->academicYear?->name,
            ]));
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logOnly(['section_id', 'adviser_teacher_id', 'name', 'status', 'is_active'])
            ->useLogName('academic_classes');
    }
}