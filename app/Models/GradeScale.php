<?php

namespace App\Models;

use Database\Factories\GradeScaleFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GradeScale extends Model
{
    /** @use HasFactory<GradeScaleFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'min_grade',
        'max_grade',
        'minimum_passing_grade',
        'decimal_precision',
        'rounding',
        'academic_year_id',
        'is_default',
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
            'min_grade' => 'decimal:2',
            'max_grade' => 'decimal:2',
            'minimum_passing_grade' => 'decimal:2',
            'decimal_precision' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * The `rounding` attribute maps to the `rounding_rule` column.
     */
    protected function rounding(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => $value ?? $this->rounding_rule ?? 'standard',
            set: fn (mixed $value) => ['rounding_rule' => $value],
        );
    }

    /**
     * @return HasMany<GradeScaleEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(GradeScaleEntry::class)->orderBy('sort_order');
    }

    /**
     * The scale range that a given grade falls within.
     *
     * @return GradeScaleEntry|null
     */
    public function entryFor(mixed $grade): ?GradeScaleEntry
    {
        if ($grade === null) {
            return null;
        }

        $value = (float) $grade;

        return $this->entries()->where('min_grade', '<=', $value)->where('max_grade', '>=', $value)->first();
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logOnly(['minimum_passing_grade', 'max_grade', 'decimal_precision', 'rounding', 'is_default', 'is_active'])
            ->useLogName('grade_scales');
    }
}