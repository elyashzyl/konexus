<?php

namespace App\Models;

use Database\Factories\AcademicClassStudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AcademicClassStudent extends Model
{
    /** @use HasFactory<AcademicClassStudentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Attribution source of the membership.
     */
    public const SOURCE_ENROLLMENT = 'enrollment';

    public const SOURCE_MANUAL = 'manual';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'academic_class_id',
        'student_id',
        'enrollment_id',
        'source',
        'academic_status',
        'remarks',
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

    public function academicClass(): BelongsTo
    {
        return $this->belongsTo(AcademicClass::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logOnly(['is_active', 'academic_status', 'remarks', 'source'])
            ->useLogName('academic_class_students');
    }
}