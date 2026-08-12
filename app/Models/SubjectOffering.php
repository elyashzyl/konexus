<?php

namespace App\Models;

use Database\Factories\SubjectOfferingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubjectOffering extends Model
{
    /** @use HasFactory<SubjectOfferingFactory> */
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
        'subject_id',
        'teacher_id',
        'department_id',
        'room_id',
        'units',
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
            'units' => 'decimal:2',
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return HasMany<ClassSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'subject_offering_id');
    }

    /**
     * @return HasMany<GradeRecord, $this>
     */
    public function gradeRecords(): HasMany
    {
        return $this->hasMany(GradeRecord::class);
    }

    /**
     * The human friendly identifier of this offering.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->subject?->name,
            $this->section?->name,
            $this->academicYear?->name,
        ]);

        return implode(' · ', $parts);
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logOnly(['teacher_id', 'room_id', 'units', 'status', 'section_id', 'is_active'])
            ->useLogName('subject_offerings');
    }
}