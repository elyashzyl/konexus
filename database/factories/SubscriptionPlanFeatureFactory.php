<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPlanFeature>
 */
class SubscriptionPlanFeatureFactory extends Factory
{
    protected $model = SubscriptionPlanFeature::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'feature_code' => fn () => fake()->randomElement([
                'students', 'enrollment', 'academic', 'attendance', 'finance',
                'reports', 'analytics', 'parent-portal', 'student-portal',
            ]),
        ];
    }
}