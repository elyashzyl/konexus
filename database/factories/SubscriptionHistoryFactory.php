<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionHistory>
 */
class SubscriptionHistoryFactory extends Factory
{
    protected $model = SubscriptionHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'action' => 'created',
            'description' => fake()->sentence(),
            'old_value' => null,
            'new_value' => null,
            'reason' => null,
            'actor_id' => null,
            'ip_address' => null,
        ];
    }
}