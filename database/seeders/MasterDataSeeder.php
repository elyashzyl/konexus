<?php

namespace Database\Seeders;

use App\Enums\MasterDataType;
use App\Models\MasterData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seed the default master data lists.
 */
class MasterDataSeeder extends Seeder
{
    /**
     * The default master data lists grouped by type.
     *
     * @var array<string, list<string>>
     */
    protected array $data = [
        'religion' => [
            'Roman Catholic', 'Islam', 'Iglesia ni Cristo', 'Seventh-day Adventist',
            'Born Again', 'Protestant', 'Buddhist', 'Jehovah\'s Witnesses',
            'Church of Christ', 'Aglipayan', 'None',
        ],
        'nationality' => [
            'Filipino', 'American', 'Chinese', 'Japanese', 'Korean', 'British', 'Indian', 'Australian', 'Other',
        ],
        'citizenship' => [
            'Filipino', 'American', 'Chinese', 'Japanese', 'Korean', 'British', 'Indian', 'Australian', 'Dual', 'Other',
        ],
        'ethnicity' => [
            'Ilocano', 'Tagalog', 'Kankanaey', 'Ibaloi', 'Ifugao', 'Bontoc', 'Bicolano', 'Visayan', 'Kapampangan', 'Other',
        ],
        'mother-tongue' => [
            'Ilocano', 'Tagalog', 'English', 'Kankanaey', 'Ibaloi', 'Ifugao', 'Bontoc', 'Bicolano', 'Visayan', 'Other',
        ],
        'relationship-type' => [
            'Father', 'Mother', 'Guardian', 'Sibling', 'Grandparent', 'Aunt', 'Uncle', 'Cousin', 'Other',
        ],
        'medical-condition' => [
            'None', 'Asthma', 'Allergies', 'Diabetes', 'Epilepsy', 'Heart Condition', 'Hypertension', 'Anemia', 'Other',
        ],
        'hospital' => [
            'Baguio General Hospital and Medical Center', 'Notre Dame de Chartres Hospital',
            'Baguio Medical Center', 'Benguet General Hospital', 'St. Louis University Hospital',
            'Pines City Doctors Hospital',
        ],
        'enrollment-status' => [
            'Draft', 'Pending', 'For Principal Approval', 'For Registrar Review', 'For Payment',
            'For Final Check', 'For Verification', 'Requirements Incomplete', 'Verified',
            'For Approval', 'Approved', 'Officially Enrolled', 'Rejected', 'Withdrawn',
            'Cancelled', 'Transferred',
        ],
        'enrollment-type' => [
            'New Student', 'Continuing', 'Returning', 'Transferee', 'Re-Enrollee',
        ],
        'enrollment-requirement-type' => [
            'Document', 'Agreement', 'Verification', 'Payment',
        ],
        'payment-status' => [
            'Unpaid', 'Partial', 'Paid', 'Overdue', 'Waived', 'Refunded',
        ],
        'student-status' => [
            'Active', 'Inactive', 'Graduated', 'Transferred', 'Dropped', 'Withdrawn', 'Suspended',
        ],
        'student-document-type' => [
            'Birth Certificate', 'Report Card', 'Good Moral Certificate', 'Transfer Credentials',
            'Medical Certificate', 'Parent Consent Form', 'ID Photo', 'School Records',
            'Certificate of Enrollment', 'Other',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (MasterDataType::cases() as $type) {
            $entries = $this->data[$type->value] ?? [];

            foreach ($entries as $index => $name) {
                MasterData::query()->firstOrCreate(
                    ['type' => $type->value, 'name' => $name],
                    [
                        'code' => $this->codeFor($type->value, $name),
                        'description' => null,
                        'is_system' => true,
                        'sort_order' => $index,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Machine codes are kept for the enrollment workflow lists so the UI and
     * the business rules reference stable values even when names are renamed.
     */
    protected function codeFor(string $type, string $name): ?string
    {
        return in_array($type, ['enrollment-status', 'enrollment-type', 'enrollment-requirement-type', 'student-status', 'student-document-type'], true)
            ? Str::kebab($name)
            : null;
    }
}
