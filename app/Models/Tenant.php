<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'school_profile_id',
        'code',
        'name',
        'status',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function schoolProfile(): BelongsTo
    {
        return $this->belongsTo(SchoolProfile::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany<License, $this>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /**
     * @return HasMany<SubscriptionInvoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /**
     * @return HasMany<SubscriptionPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * @return HasMany<SubscriptionHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    /**
     * @return HasMany<SubscriptionUsage, $this>
     */
    public function usage(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    /**
     * The current non-terminal subscription of the tenant.
     */
    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['trial', 'active', 'grace_period', 'past_due'])
            ->latest('id')
            ->first();
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly(['school_profile_id', 'code', 'name', 'status'])
            ->useLogName('tenants');
    }
}
