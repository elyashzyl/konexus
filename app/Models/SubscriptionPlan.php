<?php

namespace App\Models;

use App\Enums\Platform\BillingCycle;
use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'billing_cycle',
        'monthly_price',
        'annual_price',
        'trial_days',
        'max_students',
        'max_staff',
        'max_branches',
        'max_users',
        'max_storage_mb',
        'is_active',
        'display_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'annual_price' => 'decimal:2',
            'trial_days' => 'integer',
            'max_students' => 'integer',
            'max_staff' => 'integer',
            'max_branches' => 'integer',
            'max_users' => 'integer',
            'max_storage_mb' => 'integer',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<SubscriptionPlanFeature, $this>
     */
    public function planFeatures(): HasMany
    {
        return $this->hasMany(SubscriptionPlanFeature::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * The feature codes included in this plan.
     *
     * @return list<string>
     */
    public function featureCodes(): array
    {
        return $this->planFeatures()->pluck('feature_code')->all();
    }

    /**
     * The price applicable to the given billing cycle.
     */
    public function priceForCycle(BillingCycle|string|null $cycle): float
    {
        $cycle = $cycle instanceof BillingCycle ? $cycle->value : (string) $cycle;

        return match ($cycle) {
            BillingCycle::ANNUAL->value => (float) $this->annual_price,
            default => (float) $this->monthly_price,
        };
    }

    /**
     * The activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logOnly([
                'name',
                'code',
                'description',
                'billing_cycle',
                'monthly_price',
                'annual_price',
                'trial_days',
                'max_students',
                'max_staff',
                'max_branches',
                'max_users',
                'max_storage_mb',
                'is_active',
                'display_order',
            ])
            ->useLogName('subscription_plans');
    }
}
