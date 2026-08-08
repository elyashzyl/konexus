<?php

namespace App\Models;

use Database\Factories\SchoolProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SchoolProfile extends Model
{
    /** @use HasFactory<SchoolProfileFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'short_name',
        'school_id',
        'region',
        'division',
        'district',
        'address',
        'contact_number',
        'email',
        'website',
        'motto',
        'logo_path',
        'principal_name',
        'is_primary',
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
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The campuses that belong to this school profile.
     *
     * @return HasMany<Campus, $this>
     */
    public function campuses(): HasMany
    {
        return $this->hasMany(Campus::class);
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('school_profiles');
    }
}
