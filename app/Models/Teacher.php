<?php

namespace App\Models;

use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'prc_number',
        'license_expiration',
        'major',
        'minor',
        'advisory_class_id',
        'department_id',
        'specialization',
        'academic_load',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'license_expiration' => 'date',
            'academic_load' => 'integer',
        ];
    }

    /**
     * The employee record extended by this teacher profile.
     *
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The section this teacher currently advises.
     *
     * @return BelongsTo<Section, $this>
     */
    public function advisoryClass(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'advisory_class_id');
    }

    /**
     * The department this teacher is assigned to.
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The employee number delegated from the backing employee record.
     */
    public function getEmployeeNumberAttribute(): ?string
    {
        return $this->employee?->employee_number;
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('teachers');
    }
}
