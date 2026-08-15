<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fn () => ucwords(fake()->unique()->words(2, true)).' Plan',
            'code' => fn () => strtoupper(fake()->unique()->lexify('PLAN-????')),
            'description' => fake()->sentence(),
            'billing_cycle' => 'monthly',
            'monthly_price' => fake()->randomFloat(2, 50, 500),
            'annual_price' => fake()->randomFloat(2, 500, 5000),
            'trial_days' => 14,
            'max_students' => 1000,
            'max_staff' => 100,
            'max_branches' => 1,
            'max_users' => 50,
            'max_storage_mb' => 10240,
            'is_active' => true,
            'display_order' => 0,
        ];
    }
}