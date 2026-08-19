<?php

namespace App\Models;

use Database\Factories\GradeCorrectionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class GradeCorrection extends Model
{
    /** @use HasFactory<GradeCorrectionFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'grade_record_id',
        'student_id',
        'subject_id',
        'academic_term_id',
        'current_grade',
        'proposed_grade',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_remarks',
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
            'current_grade' => 'decimal:2',
            'proposed_grade' => 'decimal:2',
            'approved_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function gradeRecord(): BelongsTo
    {
        return $this->belongsTo(GradeRecord::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => ucfirst($this->status));
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly(['status', 'proposed_grade', 'reason', 'approval_remarks'])
            ->useLogName('grade_corrections');
    }
}