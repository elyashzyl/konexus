<?php

namespace App\Models;

use Database\Factories\LicenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class License extends Model
{
    /** @use HasFactory<LicenseFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'license_key',
        'tenant_id',
        'plan_id',
        'issued_date',
        'start_date',
        'expiration_date',
        'status',
        'max_users',
        'max_students',
        'max_branches',
        'max_storage_mb',
        'features',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'start_date' => 'date',
            'expiration_date' => 'date',
            'features' => 'array',
            'max_users' => 'integer',
            'max_students' => 'integer',
            'max_branches' => 'integer',
            'max_storage_mb' => 'integer',
            'license_key' => 'encrypted',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The masked representation of the license key shown to non-privileged
     * viewers, e.g. KONX-****-****-A82F.
     */
    public function maskedKey(): string
    {
        $key = (string) $this->license_key;

        if ($key === '') {
            return '';
        }

        $segments = explode('-', $key);

        return count($segments) >= 3
            ? $segments[0].'-****-****-'.end($segments)
            : substr($key, 0, 4).'-****';
    }

    /**
     * The activity log options for this model. The key itself is never logged.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logOnly([
                'tenant_id',
                'plan_id',
                'issued_date',
                'start_date',
                'expiration_date',
                'status',
            ])
            ->useLogName('licenses');
    }
}