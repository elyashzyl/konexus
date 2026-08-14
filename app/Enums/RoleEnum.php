<?php

namespace App\Enums;

/**
 * The default system roles available in KONEXUS.
 *
 * @phpstan-type RoleValues array{key: string, label: string, description: string}
 */
enum RoleEnum: string
{
    case SUPER_ADMINISTRATOR = 'super-administrator';
    case PLATFORM_ADMINISTRATOR = 'platform-administrator';
    case SCHOOL_ADMINISTRATOR = 'school-administrator';
    case PRINCIPAL = 'principal';
    case REGISTRAR = 'registrar';
    case FINANCE_OFFICER = 'finance-officer';
    case TEACHER = 'teacher';
    case ADVISER = 'adviser';
    case GUIDANCE_COUNSELOR = 'guidance-counselor';
    case SCHOOL_NURSE = 'school-nurse';
    case LIBRARIAN = 'librarian';
    case HR_OFFICER = 'hr-officer';
    case INVENTORY_OFFICER = 'inventory-officer';
    case STUDENT = 'student';
    case PARENT = 'parent';

    /**
     * Human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMINISTRATOR => 'Super Administrator',
            self::PLATFORM_ADMINISTRATOR => 'Platform Administrator',
            self::SCHOOL_ADMINISTRATOR => 'School Administrator',
            self::PRINCIPAL => 'Principal',
            self::REGISTRAR => 'Registrar',
            self::FINANCE_OFFICER => 'Finance Officer',
            self::TEACHER => 'Teacher',
            self::ADVISER => 'Adviser',
            self::GUIDANCE_COUNSELOR => 'Guidance Counselor',
            self::SCHOOL_NURSE => 'School Nurse',
            self::LIBRARIAN => 'Librarian',
            self::HR_OFFICER => 'Human Resource Officer',
            self::INVENTORY_OFFICER => 'Inventory Officer',
            self::STUDENT => 'Student',
            self::PARENT => 'Parent',
        };
    }

    /**
     * Short description of the role's responsibilities.
     */
    public function description(): string
    {
        return match ($this) {
            self::SUPER_ADMINISTRATOR => 'Full system access. Manages modules, users, permissions and platform configuration.',
            self::PLATFORM_ADMINISTRATOR => 'Platform-level management of tenants, subscription plans, licenses, billing and usage.',
            self::SCHOOL_ADMINISTRATOR => 'Overall school management, operational modules and school configuration.',
            self::PRINCIPAL => 'Academic oversight, reports, faculty supervision and student monitoring.',
            self::REGISTRAR => 'Student records, enrollment, academic records, section assignment and student documents.',
            self::FINANCE_OFFICER => 'Billing, assessment, payments and financial reports.',
            self::TEACHER => 'Subjects, attendance, grades and class management.',
            self::ADVISER => 'Advisory class, attendance, student monitoring and parent communication.',
            self::GUIDANCE_COUNSELOR => 'Guidance records, counseling, student behavior, case management and student welfare.',
            self::SCHOOL_NURSE => 'Clinic management, medical records, health monitoring, medical clearances and vaccination records.',
            self::LIBRARIAN => 'Library management, book catalog, borrowing, returns and inventory.',
            self::HR_OFFICER => 'Employee records, leave management and personnel information.',
            self::INVENTORY_OFFICER => 'School assets, equipment and supply inventory.',
            self::STUDENT => 'Student portal: enrollment status, grades, attendance and schedule.',
            self::PARENT => 'Parent portal: student progress, billing, attendance and grades.',
        };
    }

    /**
     * The normalized role name stored in the database.
     */
    public function roleName(): string
    {
        return $this->value;
    }

    /**
     * All system roles as an array of [key, label, description].
     *
     * @return list<array{key: string, label: string, description: string}>
     */
    public static function toSeedData(): array
    {
        return array_map(
            static fn (self $role) => [
                'key' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
            ],
            self::cases()
        );
    }
}
