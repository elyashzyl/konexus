<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionFeature>
 */
class SubscriptionFeatureFactory extends Factory
{
    protected $model = SubscriptionFeature::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'feature_code' => 'attendance',
            'is_enabled' => true,
        ];
    }
}