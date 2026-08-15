<?php

namespace Database\Factories;

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\AttendanceSession;
use App\Models\Campus;
use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceSession> */
class AttendanceSessionFactory extends Factory
{
    protected $model = AttendanceSession::class;

    public function definition(): array
    {
        return [
            'academic_class_id' => AcademicClass::factory()->state([
                'academic_year_id' => AcademicYear::factory(),
                'campus_id' => Campus::factory(),
                'grade_level_id' => GradeLevel::factory(),
                'section_id' => Section::factory(),
                'adviser_teacher_id' => null,
            ]),
            'attendance_date' => fake()->date(),
            'status' => 'open',
        ];
    }
}
