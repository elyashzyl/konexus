<?php

namespace App\Models;

use Database\Factories\ParentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ParentGuardian extends Model
{
    /** @use HasFactory<ParentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'parents';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'occupation',
        'employer',
        'educational_attainment',
        'mobile_number',
        'telephone_number',
        'email',
        'address',
        'relationship',
        'not_applicable',
        'maiden_name',
        'status',
        'user_id',
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
            'not_applicable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The children linked to this parent.
     *
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * The user account linked to this parent (portal identity).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The full name of the parent.
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
            ->dontLogEmptyChanges()
            ->useLogName('parents');
    }
}
