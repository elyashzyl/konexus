<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_profile_id' => null,
            'code' => fn () => 'TEN-'.strtoupper(fake()->unique()->bothify('####')),
            'name' => fn () => fake()->company().' School',
            'status' => 'active',
            'settings' => null,
        ];
    }
}