<?php

namespace App\Models;

use Database\Factories\CurriculumEntryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CurriculumEntry extends Model
{
    /** @use HasFactory<CurriculumEntryFactory> */
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
        'subject_id',
        'subject_type',
        'units',
        'is_required',
        'display_order',
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
            'is_required' => 'boolean',
            'display_order' => 'integer',
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    protected function displayLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => optional($this->academicTerm)->name
                ? sprintf('%s · %s', $this->academicYear?->name, $this->academicTerm->name)
                : (string) ($this->academicYear?->name ?? 'Curriculum')
        );
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logOnly(['subject_type', 'units', 'is_required', 'display_order', 'status', 'is_active'])
            ->useLogName('curriculum');
    }
}