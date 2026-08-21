<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\RequirementItemStatus;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\HasActivity;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory, HasActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'school_profile_id',
        'student_id',
        'academic_year_id',
        'curriculum_program_id',
        'academic_term_id',
        'campus_id',
        'grade_level_id',
        'section_id',
        'department',
        'strand',
        'track',
        'program_cluster',
        'elective_selections',
        'incoming_level',
        'email',
        'mobile_number',
        'application_submitted_at',
        'application_expires_at',
        'siblings',
        'tuition_plan',
        'medical_history',
        'chinese_details',
        'photo_consent',
        'online_photo_sharing',
        'registration_consent',
        'credentialing_consent',
        'rules_consent',
        'mother_confirmation',
        'father_confirmation',
        'date_of_registration',
        'initial_payment',
        'initial_payment_status',
        'account_settings',
        'is_withdrawn_student',
        'is_sanctioned',
        'is_officially_enrolled',
        'enrollment_number',
        'reference_number',
        'status',
        'enrollment_type',
        'enrollment_date',
        'date_enrolled',
        'transfer_date',
        'transfer_type',
        'transfer_destination',
        'transfer_destination_school',
        'transfer_reason',
        'transfer_remarks',
        'payment_status',
        'payment_method',
        'down_payment',
        'payment_schedule_date',
        'payment_schedule_details',
        'approved_by',
        'approved_at',
        'principal_approved_by',
        'principal_approved_at',
        'registrar_reviewed_by',
        'registrar_reviewed_at',
        'payment_recorded_by',
        'payment_recorded_at',
        'final_checked_by',
        'final_checked_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'withdrawn_by',
        'withdrawn_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'notes',
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
            'enrollment_date' => 'date',
            'date_enrolled' => 'date',
            'transfer_date' => 'date',
            'down_payment' => 'decimal:2',
            'payment_schedule_date' => 'date',
            'application_submitted_at' => 'datetime',
            'application_expires_at' => 'datetime',
            'siblings' => 'array',
            'elective_selections' => 'array',
            'medical_history' => 'array',
            'chinese_details' => 'array',
            'photo_consent' => 'boolean',
            'online_photo_sharing' => 'boolean',
            'registration_consent' => 'boolean',
            'credentialing_consent' => 'boolean',
            'rules_consent' => 'boolean',
            'mother_confirmation' => 'boolean',
            'father_confirmation' => 'boolean',
            'date_of_registration' => 'date',
            'initial_payment' => 'decimal:2',
            'account_settings' => 'array',
            'is_withdrawn_student' => 'boolean',
            'is_sanctioned' => 'boolean',
            'is_officially_enrolled' => 'boolean',
            'approved_at' => 'datetime',
            'principal_approved_at' => 'datetime',
            'registrar_reviewed_at' => 'datetime',
            'payment_recorded_at' => 'datetime',
            'final_checked_at' => 'datetime',
            'rejected_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function curriculumProgram(): BelongsTo
    {
        return $this->belongsTo(CurriculumProgram::class);
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function principalApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'principal_approved_by');
    }

    public function registrarReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrar_reviewed_by');
    }

    public function paymentRecordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_recorded_by');
    }

    public function finalCheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_checked_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function withdrawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return HasMany<EnrollmentRequirementItem, $this>
     */
    public function requirementItems(): HasMany
    {
        return $this->hasMany(EnrollmentRequirementItem::class);
    }

    /**
     * @return HasMany<EnrollmentDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EnrollmentDocument::class);
    }

    /**
     * @return HasMany<EnrollmentTransfer, $this>
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(EnrollmentTransfer::class);
    }

    /**
     * @return HasMany<EnrollmentCapacityOverride, $this>
     */
    public function capacityOverrides(): HasMany
    {
        return $this->hasMany(EnrollmentCapacityOverride::class);
    }

    /**
     * @return HasMany<EnrollmentSignature, $this>
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(EnrollmentSignature::class);
    }

    /**
     * Whether all required requirements have been satisfied (verified/not-required).
     */
    public function allRequirementsSatisfied(): bool
    {
        $items = $this->relationLoaded('requirementItems.requirement')
            ? $this->requirementItems
            : $this->requirementItems()->with('requirement')->get();

        $required = $items->filter(
            fn (EnrollmentRequirementItem $item) => $item->requirement !== null && (bool) $item->requirement->is_required
        );

        if ($required->isEmpty()) {
            return true;
        }

        return $required->every(fn (EnrollmentRequirementItem $item) => in_array(
            $item->status,
            RequirementItemStatus::satisfiedStatuses(),
            true
        ));
    }

    public function getDisplayStatusLabelAttribute(): string
    {
        return EnrollmentStatus::tryFrom($this->status)?->label() ?? ucfirst($this->status);
    }

    /**
     * Scope to enrollments still within an active workflow state.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', EnrollmentStatus::activeStatuses());
    }

    /**
     * Scope for a free-text search across the identifier columns.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('enrollment_number', 'like', "%{$term}%")
                ->orWhere('reference_number', 'like', "%{$term}%");
        });
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly(['status', 'section_id', 'grade_level_id', 'date_enrolled'])
            ->useLogName('enrollments');
    }
}
