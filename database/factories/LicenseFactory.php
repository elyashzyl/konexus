<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    protected $model = License::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', '-1 day');

        return [
            'license_key' => fn () => 'KONX-'.strtoupper(fake()->unique()->bothify('####')).'-'.strtoupper(fake()->unique()->bothify('####')).'-'.strtoupper(fake()->unique()->bothify('####')),
            'tenant_id' => Tenant::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'issued_date' => $start->format('Y-m-d'),
            'start_date' => $start->format('Y-m-d'),
            'expiration_date' => (clone $start)->modify('+1 year')->format('Y-m-d'),
            'status' => 'active',
            'max_users' => 50,
            'max_students' => 1000,
            'max_branches' => 1,
            'max_storage_mb' => 10240,
            'features' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}