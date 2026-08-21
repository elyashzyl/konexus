<?php

namespace App\Models;

use Database\Factories\EnrollmentRequirementItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EnrollmentRequirementItem extends Model
{
    /** @use HasFactory<EnrollmentRequirementItemFactory> */
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'enrollment_id',
        'enrollment_requirement_id',
        'status',
        'remarks',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * The human readable label of the requirement item status.
     */
    public function getStatusLabelAttribute(): string
    {
        return \App\Enums\RequirementItemStatus::tryFrom($this->status)?->label() ?? ucfirst((string) $this->status);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(EnrollmentRequirement::class, 'enrollment_requirement_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * @return HasMany<EnrollmentDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EnrollmentDocument::class, 'requirement_item_id');
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logOnly(['status', 'remarks'])
            ->useLogName('enrollment_requirement_items');
    }
}