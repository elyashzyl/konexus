<?php

namespace App\Models;

use App\Enums\Platform\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_code',
        'tenant_id',
        'plan_id',
        'status',
        'start_date',
        'expiration_date',
        'trial_started_at',
        'trial_ends_at',
        'trial_status',
        'billing_cycle',
        'amount',
        'auto_renewal',
        'grace_days',
        'grace_ends_at',
        'expiration_behavior',
        'last_renewed_at',
        'cancelled_at',
        'cancel_reason',
        'suspended_at',
        'suspend_reason',
        'expected_resume_at',
        'resumed_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expiration_date' => 'date',
            'trial_started_at' => 'date',
            'trial_ends_at' => 'date',
            'grace_ends_at' => 'date',
            'last_renewed_at' => 'date',
            'cancelled_at' => 'datetime',
            'suspended_at' => 'datetime',
            'expected_resume_at' => 'date',
            'resumed_at' => 'datetime',
            'amount' => 'decimal:2',
            'auto_renewal' => 'boolean',
            'grace_days' => 'integer',
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

    /**
     * @return HasMany<SubscriptionFeature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(SubscriptionFeature::class);
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
     * Whether the subscription currently allows normal usage.
     */
    public function allowsAccess(): bool
    {
        return SubscriptionStatus::tryFrom($this->status)?->allowsAccess() ?? false;
    }

    /**
     * The remaining days until the subscription expires.
     */
    public function daysRemaining(): int
    {
        if (! $this->expiration_date) {
            return 0;
        }

        return (int) max(0, Carbon::today()->diffInDays($this->expiration_date, false));
    }

    /**
     * Whether the subscription is currently in its grace period.
     */
    public function inGracePeriod(): bool
    {
        return $this->status === SubscriptionStatus::GRACE_PERIOD->value
            && $this->grace_ends_at
            && Carbon::today()->lte($this->grace_ends_at);
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logOnly([
                'tenant_id',
                'plan_id',
                'status',
                'expiration_date',
                'billing_cycle',
                'amount',
                'auto_renewal',
                'grace_days',
                'expiration_behavior',
            ])
            ->useLogName('subscriptions');
    }
}
