<?php

namespace App\Models;

use Database\Factories\EnrollmentRequirementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class EnrollmentRequirement extends Model
{
    /** @use HasFactory<EnrollmentRequirementFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_required',
        'type',
        'applicable_grade_levels',
        'applicable_enrollment_types',
        'applicable_academic_year_id',
        'applicable_campus_ids',
        'sort_order',
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
            'is_required' => 'boolean',
            'applicable_grade_levels' => 'array',
            'applicable_enrollment_types' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'applicable_academic_year_id');
    }

    /**
     * @return HasMany<EnrollmentRequirementItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(EnrollmentRequirementItem::class);
    }

    /**
     * Whether this requirement applies to the given enrollment context.
     */
    public function appliesTo(Enrollment $enrollment): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->applicable_academic_year_id && (int) $this->applicable_academic_year_id !== (int) $enrollment->academic_year_id) {
            return false;
        }

        $gradeLevels = $this->applicable_grade_levels ?: [];
        if ($gradeLevels && ! in_array((string) $enrollment->grade_level_id, array_map('strval', $gradeLevels), true)) {
            return false;
        }

        $types = $this->applicable_enrollment_types ?: [];
        if ($types && ! in_array($enrollment->enrollment_type, $types, true)) {
            return false;
        }

        if ($this->applicable_campus_ids) {
            $campusIds = array_filter(array_map('trim', explode(',', (string) $this->applicable_campus_ids)));
            if ($campusIds && ! in_array((string) $enrollment->campus_id, $campusIds, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('enrollment_requirements');
    }
}