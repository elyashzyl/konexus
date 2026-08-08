<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed the Phase 2 core school framework and system configuration.
 */
class Phase2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SystemSettingsSeeder::class,
            SchoolProfileSeeder::class,
            AcademicStructureSeeder::class,
            DepartmentsAndSubjectsSeeder::class,
            FacilitiesSeeder::class,
            SchoolCalendarSeeder::class,
            AnnouncementsSeeder::class,
            MasterDataSeeder::class,
        ]);
    }
}
