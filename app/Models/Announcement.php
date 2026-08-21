<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'campus_id',
        'title',
        'content',
        'category',
        'priority',
        'target_audience',
        'audience',
        'author_id',
        'created_by',
        'published',
        'published_at',
        'status',
        'scheduled_at',
        'starts_at',
        'ends_at',
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
            'audience' => 'array',
            'published' => 'boolean',
            'published_at' => 'datetime',
            'status' => 'string',
            'scheduled_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The user who authored this announcement.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The campus this announcement belongs to.
     *
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * The school profile this announcement belongs to.
     *
     * @return BelongsTo<SchoolProfile, $this>
     */
    public function schoolProfile(): BelongsTo
    {
        return $this->belongsTo(SchoolProfile::class);
    }

    /**
     * The user who created this announcement record.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Whether this announcement is currently visible to readers, honoring the
     * publish status and the configured display window.
     */
    public function isVisible(): bool
    {
        if ($this->status === 'archived' || ! $this->is_active) {
            return false;
        }

        if ($this->status === 'draft') {
            return false;
        }

        if ($this->status === 'scheduled') {
            return $this->scheduled_at !== null && $this->scheduled_at->isPast();
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Whether this announcement targets the given audience signature.
     *
     * @param  array<string, mixed>  $signature
     */
    public function matchesAudience(array $signature): bool
    {
        $audience = $this->audience ?? [];
        $roles = $audience['roles'] ?? [];

        if ($this->target_audience === 'all'
            || in_array('everyone', $this->audience ?? ['everyone'], true)
            || in_array('everyone', $roles, true)) {
            return true;
        }

        if (! empty($audience['roles']) && in_array($signature['role'] ?? null, $audience['roles'], true)) {
            return true;
        }

        if (! empty($audience['grade_level_ids']) && in_array($signature['grade_level_id'] ?? null, $audience['grade_level_ids'], true)) {
            return true;
        }

        if (! empty($audience['section_ids']) && in_array($signature['section_id'] ?? null, $audience['section_ids'], true)) {
            return true;
        }

        if (! empty($audience['campus_ids']) && in_array($signature['campus_id'] ?? null, $audience['campus_ids'], true)) {
            return true;
        }

        if (($signature['role'] ?? null) === 'student' && in_array('students', $this->targetAudienceTags(), true)) {
            return true;
        }

        if (($signature['role'] ?? null) === 'parent' && in_array('parents', $this->targetAudienceTags(), true)) {
            return true;
        }

        if (($signature['role'] ?? null) === 'teacher' && in_array('teachers', $this->targetAudienceTags(), true)) {
            return true;
        }

        if (($signature['role'] ?? null) === 'staff' && in_array('staff', $this->targetAudienceTags(), true)) {
            return true;
        }

        return false;
    }

    /**
     * The legacy target audience split into tags for backwards compatibility.
     *
     * @return list<string>
     */
    protected function targetAudienceTags(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $this->target_audience)
        )));
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('announcements');
    }
}
