<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'gender',
        'birth_date',
        'mobile_number',
        'telephone_number',
        'email',
        'address',
        'employment_type',
        'department_id',
        'position',
        'hiring_type',
        'date_hired',
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
            'birth_date' => 'date',
            'date_hired' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The department this employee belongs to.
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The teaching profile extending this employee record.
     *
     * @return HasOne<Teacher, $this>
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * The non-teaching profile extending this employee record.
     *
     * @return HasOne<Staff, $this>
     */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Determine whether this employee is a teaching personnel.
     */
    public function getIsTeacherAttribute(): bool
    {
        return $this->employment_type === 'teaching';
    }

    /**
     * The full name of the employee.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->extension_name,
        ], fn (?string $part) => filled($part));

        return implode(' ', $parts);
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('employees');
    }
}
