<?php

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'campus_id',
        'name',
        'code',
        'description',
        'department_id',
        'grade_level_id',
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

    /**
     * The department this subject belongs to.
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The campus this subject belongs to.
     *
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * The school profile this subject belongs to.
     *
     * @return BelongsTo<SchoolProfile, $this>
     */
    public function schoolProfile(): BelongsTo
    {
        return $this->belongsTo(SchoolProfile::class);
    }

    /**
     * The grade level this subject is offered to.
     *
     * @return BelongsTo<GradeLevel, $this>
     */
    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * The offerings of this subject across sections and academic years.
     *
     * @return HasMany<SubjectOffering, $this>
     */
    public function offerings(): HasMany
    {
        return $this->hasMany(SubjectOffering::class);
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('subjects');
    }
}
