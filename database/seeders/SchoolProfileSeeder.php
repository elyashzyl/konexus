<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\SchoolProfile;
use Illuminate\Database\Seeder;

/**
 * Seed the default school profile and its primary campus.
 */
class SchoolProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profile = SchoolProfile::query()->firstOrCreate(
            ['name' => 'Baguio Patriotic High School'],
            [
                'short_name' => 'BPHS',
                'school_id' => '300001',
                'region' => 'Cordillera Administrative Region',
                'division' => 'Baguio City',
                'district' => 'Baguio City',
                'address' => 'T. Alonzo St., Baguio City, Benguet, Philippines',
                'contact_number' => '(074) 446-1234',
                'email' => 'bphs.baguio@deped.gov.ph',
                'website' => null,
                'motto' => 'Husay, Disiplina at Kagalingan',
                'logo_path' => null,
                'principal_name' => 'Dr. Juan P. Dela Cruz',
                'is_primary' => true,
                'is_active' => true,
            ]
        );

        Campus::query()->firstOrCreate(
            ['name' => 'Baguio Patriotic High School Main Campus'],
            [
                'school_profile_id' => $profile->id,
                'code' => 'MAIN',
                'address' => 'T. Alonzo St., Baguio City, Benguet, Philippines',
                'contact_number' => '(074) 446-1234',
                'is_active' => true,
            ]
        );
    }
}
