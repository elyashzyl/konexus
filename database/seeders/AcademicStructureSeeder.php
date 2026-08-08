<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Database\Seeder;

/**
 * Seed the default academic structure: the active academic year with its
 * quarterly terms, the grade levels and their sections.
 */
class AcademicStructureSeeder extends Seeder
{
    /**
     * The default academic year.
     *
     * @var array{name: string, start_date: string, end_date: string, calendar_type: string}
     */
    protected array $academicYear = [
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'calendar_type' => 'quarterly',
    ];

    /**
     * The quarterly terms of the default academic year.
     *
     * @var list<array{name: string, code: string, sequence: int, start_date: string, end_date: string}>
     */
    protected array $terms = [
        ['name' => '1st Quarter', 'code' => 'Q1', 'sequence' => 1, 'start_date' => '2026-06-01', 'end_date' => '2026-08-31'],
        ['name' => '2nd Quarter', 'code' => 'Q2', 'sequence' => 2, 'start_date' => '2026-09-01', 'end_date' => '2026-11-30'],
        ['name' => '3rd Quarter', 'code' => 'Q3', 'sequence' => 3, 'start_date' => '2026-12-01', 'end_date' => '2027-02-28'],
        ['name' => '4th Quarter', 'code' => 'Q4', 'sequence' => 4, 'start_date' => '2027-03-01', 'end_date' => '2027-03-31'],
    ];

    /**
     * The default grade levels.
     *
     * @var list<array{name: string, code: string, short_name: string, education_level: string, sequence: int}>
     */
    protected array $gradeLevels = [
        ['name' => 'Grade 7', 'code' => '7', 'short_name' => 'G7', 'education_level' => 'junior-high', 'sequence' => 7],
        ['name' => 'Grade 8', 'code' => '8', 'short_name' => 'G8', 'education_level' => 'junior-high', 'sequence' => 8],
        ['name' => 'Grade 9', 'code' => '9', 'short_name' => 'G9', 'education_level' => 'junior-high', 'sequence' => 9],
        ['name' => 'Grade 10', 'code' => '10', 'short_name' => 'G10', 'education_level' => 'junior-high', 'sequence' => 10],
        ['name' => 'Grade 11', 'code' => '11', 'short_name' => 'G11', 'education_level' => 'senior-high', 'sequence' => 11],
        ['name' => 'Grade 12', 'code' => '12', 'short_name' => 'G12', 'education_level' => 'senior-high', 'sequence' => 12],
    ];

    /**
     * The sections created for each grade level.
     *
     * @var array<string, list<string>>
     */
    protected array $sections = [
        'Grade 7' => ['Diamond', 'Emerald'],
        'Grade 8' => ['Amethyst', 'Topaz'],
        'Grade 9' => ['Ruby', 'Sapphire'],
        'Grade 10' => ['Gold', 'Silver'],
        'Grade 11' => ['STEM', 'ABM', 'HUMSS'],
        'Grade 12' => ['STEM', 'ABM', 'HUMSS'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = AcademicYear::query()->firstOrCreate(
            ['name' => $this->academicYear['name']],
            [
                'code' => '2026',
                'start_date' => $this->academicYear['start_date'],
                'end_date' => $this->academicYear['end_date'],
                'calendar_type' => $this->academicYear['calendar_type'],
                'is_active' => true,
                'description' => 'School Year '.$this->academicYear['name'],
            ]
        );

        foreach ($this->terms as $index => $term) {
            AcademicTerm::query()->firstOrCreate(
                ['academic_year_id' => $year->id, 'name' => $term['name']],
                [
                    'code' => $term['code'],
                    'sequence' => $term['sequence'],
                    'start_date' => $term['start_date'],
                    'end_date' => $term['end_date'],
                    'is_active' => $index === 0,
                ]
            );
        }

        foreach ($this->gradeLevels as $grade) {
            $gradeLevel = GradeLevel::query()->firstOrCreate(
                ['name' => $grade['name']],
                [
                    'code' => $grade['code'],
                    'short_name' => $grade['short_name'],
                    'education_level' => $grade['education_level'],
                    'sequence' => $grade['sequence'],
                    'is_active' => true,
                ]
            );

            foreach ($this->sections[$grade['name']] ?? [] as $sectionName) {
                Section::query()->firstOrCreate(
                    ['grade_level_id' => $gradeLevel->id, 'name' => $sectionName],
                    [
                        'code' => $grade['code'].'-'.strtoupper($sectionName),
                        'max_capacity' => 45,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
