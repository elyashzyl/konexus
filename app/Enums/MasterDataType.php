<?php

namespace App\Enums;

/**
 * The master data list categories managed by the Master Data module.
 *
 * These lists feed dropdowns in the student, HR, finance and clinic modules
 * that will be built in later phases.
 */
enum MasterDataType: string
{
    case RELIGION = 'religion';
    case NATIONALITY = 'nationality';
    case CITIZENSHIP = 'citizenship';
    case ETHNICITY = 'ethnicity';
    case MOTHER_TONGUE = 'mother-tongue';
    case RELATIONSHIP_TYPE = 'relationship-type';
    case MEDICAL_CONDITION = 'medical-condition';
    case HOSPITAL = 'hospital';
    case ENROLLMENT_STATUS = 'enrollment-status';
    case PAYMENT_STATUS = 'payment-status';
    case STUDENT_STATUS = 'student-status';

    /**
     * Human readable label for the list type.
     */
    public function label(): string
    {
        return match ($this) {
            self::RELIGION => 'Religions',
            self::NATIONALITY => 'Nationalities',
            self::CITIZENSHIP => 'Citizenships',
            self::ETHNICITY => 'Ethnicities',
            self::MOTHER_TONGUE => 'Mother Tongues',
            self::RELATIONSHIP_TYPE => 'Relationship Types',
            self::MEDICAL_CONDITION => 'Medical Conditions',
            self::HOSPITAL => 'Hospitals',
            self::ENROLLMENT_STATUS => 'Enrollment Statuses',
            self::PAYMENT_STATUS => 'Payment Statuses',
            self::STUDENT_STATUS => 'Student Statuses',
        };
    }

    /**
     * All master data types as [value, label].
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toSeedData(): array
    {
        return array_map(
            static fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}
