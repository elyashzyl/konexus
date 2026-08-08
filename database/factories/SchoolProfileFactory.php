<?php

namespace Database\Factories;

use App\Models\SchoolProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolProfile>
 */
class SchoolProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SchoolProfile>
     */
    protected $model = SchoolProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' High School',
            'short_name' => fake()->lexify('??HS'),
            'school_id' => fake()->numerify('######'),
            'region' => 'Cordillera Administrative Region',
            'division' => 'Baguio City',
            'district' => fake()->city(),
            'address' => fake()->address(),
            'contact_number' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'website' => null,
            'motto' => fake()->sentence(4),
            'logo_path' => null,
            'principal_name' => fake()->name(),
            'is_primary' => false,
            'is_active' => true,
        ];
    }
}
