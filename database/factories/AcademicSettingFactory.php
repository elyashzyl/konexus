<?php

namespace Database\Factories;

use App\Models\AcademicSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicSetting>
 */
class AcademicSettingFactory extends Factory
{
    protected $model = AcademicSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'group' => 'academic',
            'value' => null,
            'type' => 'string',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}