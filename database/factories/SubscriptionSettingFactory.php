<?php

namespace Database\Factories;

use App\Models\SubscriptionSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionSetting>
 */
class SubscriptionSettingFactory extends Factory
{
    protected $model = SubscriptionSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fn () => fake()->unique()->slug(2),
            'value' => fake()->word(),
            'group' => 'general',
            'type' => 'string',
            'description' => null,
            'is_active' => true,
        ];
    }
}