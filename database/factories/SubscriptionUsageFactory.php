<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionUsage>
 */
class SubscriptionUsageFactory extends Factory
{
    protected $model = SubscriptionUsage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'period_year' => (int) date('Y'),
            'period_month' => (int) date('n'),
            'students_count' => fake()->numberBetween(0, 500),
            'users_count' => fake()->numberBetween(0, 100),
            'staff_count' => fake()->numberBetween(0, 100),
            'branches_count' => 1,
            'storage_mb' => fake()->numberBetween(0, 5000),
            'documents_count' => fake()->numberBetween(0, 200),
            'database_size_mb' => fake()->numberBetween(0, 1000),
            'captured_at' => now(),
        ];
    }
}