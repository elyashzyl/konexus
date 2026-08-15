<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', '-1 day');

        return [
            'subscription_code' => fn () => 'SUB-'.strtoupper(fake()->unique()->bothify('######')),
            'tenant_id' => Tenant::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'status' => 'active',
            'start_date' => $start,
            'expiration_date' => (clone $start)->modify('+1 year'),
            'trial_started_at' => null,
            'trial_ends_at' => null,
            'trial_status' => null,
            'billing_cycle' => 'monthly',
            'amount' => 299.00,
            'auto_renewal' => true,
            'grace_days' => 7,
            'grace_ends_at' => null,
            'expiration_behavior' => 'grace_period',
            'last_renewed_at' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'suspended_at' => null,
            'suspend_reason' => null,
            'expected_resume_at' => null,
            'resumed_at' => null,
            'notes' => null,
        ];
    }
}