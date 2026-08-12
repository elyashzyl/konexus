<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Guardian;
use App\Models\ParentGuardian;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

/**
 * Seed the Part 3 – People Management module with sample data.
 */
class PeopleManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Employees power both teachers and staff profiles.
        $employees = Employee::factory()->count(20)->create();

        $employees->where('employment_type', 'teaching')->each(function (Employee $employee): void {
            Teacher::factory()->create([
                'employee_id' => $employee->id,
            ]);
        });

        $employees->where('employment_type', 'non-teaching')->each(function (Employee $employee): void {
            Staff::factory()->create([
                'employee_id' => $employee->id,
            ]);
        });

        // Students, each linked to a parent and a guardian.
        Student::factory()->count(25)->create()->each(function (Student $student): void {
            $parent = ParentGuardian::factory()->create();
            $guardian = Guardian::factory()->create();

            $student->parents()->attach($parent, ['is_primary' => true]);
            $student->guardians()->attach($guardian, ['is_primary' => true]);
        });
    }
}
