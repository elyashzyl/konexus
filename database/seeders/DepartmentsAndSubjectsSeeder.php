<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Seed the default departments and subjects.
 */
class DepartmentsAndSubjectsSeeder extends Seeder
{
    /**
     * The default departments.
     *
     * @var list<array{name: string, code: string}>
     */
    protected array $departments = [
        ['name' => 'English Department', 'code' => 'ENG'],
        ['name' => 'Filipino Department', 'code' => 'FIL'],
        ['name' => 'Mathematics Department', 'code' => 'MATH'],
        ['name' => 'Science Department', 'code' => 'SCI'],
        ['name' => 'Araling Panlipunan Department', 'code' => 'AP'],
        ['name' => 'Edukasyon sa Pagpapakatao Department', 'code' => 'ESP'],
        ['name' => 'MAPEH Department', 'code' => 'MAPEH'],
        ['name' => 'Technology and Livelihood Education Department', 'code' => 'TLE'],
    ];

    /**
     * The junior high school subjects (per grade level).
     *
     * @var array<string, list<array{name: string, code: string, department: string}>>
     */
    protected array $juniorHighSubjects = [
        'Grade 7' => [
            ['name' => 'English 7', 'code' => 'ENG7', 'department' => 'ENG'],
            ['name' => 'Filipino 7', 'code' => 'FIL7', 'department' => 'FIL'],
            ['name' => 'Mathematics 7', 'code' => 'MATH7', 'department' => 'MATH'],
            ['name' => 'Science 7', 'code' => 'SCI7', 'department' => 'SCI'],
            ['name' => 'Araling Panlipunan 7', 'code' => 'AP7', 'department' => 'AP'],
            ['name' => 'Edukasyon sa Pagpapakatao 7', 'code' => 'ESP7', 'department' => 'ESP'],
            ['name' => 'MAPEH 7', 'code' => 'MAPEH7', 'department' => 'MAPEH'],
            ['name' => 'TLE 7', 'code' => 'TLE7', 'department' => 'TLE'],
        ],
    ];

    /**
     * The senior high school subjects (offered to both SHS grade levels).
     *
     * @var list<array{name: string, code: string, department: string}>
     */
    protected array $seniorHighSubjects = [
        ['name' => 'Komunikasyon at Pananaliksik sa Wika at Kulturang Filipino', 'code' => 'FIL', 'department' => 'FIL'],
        ['name' => '21st Century Literature from the Philippines and the World', 'code' => 'ENG', 'department' => 'ENG'],
        ['name' => 'General Mathematics', 'code' => 'MATH', 'department' => 'MATH'],
        ['name' => 'Earth and Life Science', 'code' => 'SCI', 'department' => 'SCI'],
        ['name' => 'Contemporary Philippine Arts from the Regions', 'code' => 'MAPEH', 'department' => 'MAPEH'],
        ['name' => 'Personal Development', 'code' => 'PD', 'department' => 'ESP'],
        ['name' => 'Understanding Culture, Society and Politics', 'code' => 'AP', 'department' => 'AP'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departmentModels = [];

        foreach ($this->departments as $department) {
            $departmentModels[$department['code']] = Department::query()->firstOrCreate(
                ['name' => $department['name']],
                ['code' => $department['code'], 'is_active' => true]
            );
        }

        foreach ($this->juniorHighSubjects as $gradeName => $subjects) {
            $gradeLevel = GradeLevel::query()->where('name', $gradeName)->first();

            foreach ($subjects as $subject) {
                $this->createSubject($subject['name'], $subject['code'], $gradeLevel?->id, $departmentModels[$subject['department']] ?? null);
            }
        }

        foreach (['Grade 11', 'Grade 12'] as $gradeName) {
            $gradeLevel = GradeLevel::query()->where('name', $gradeName)->first();

            foreach ($this->seniorHighSubjects as $subject) {
                $this->createSubject($subject['name'], $subject['code'].$gradeLevel->code, $gradeLevel->id, $departmentModels[$subject['department']] ?? null);
            }
        }
    }

    /**
     * Create a subject when it does not exist.
     */
    private function createSubject(string $name, string $code, ?int $gradeLevelId, ?Department $department): void
    {
        Subject::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'description' => null,
                'department_id' => $department?->id,
                'grade_level_id' => $gradeLevelId,
                'is_active' => true,
            ]
        );
    }
}
