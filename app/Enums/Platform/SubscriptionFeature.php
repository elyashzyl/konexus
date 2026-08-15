<?php

namespace App\Enums\Platform;

/**
 * The module features that can be gated by a subscription plan.
 */
enum SubscriptionFeature: string
{
    case STUDENTS = 'students';
    case ENROLLMENT = 'enrollment';
    case ACADEMIC = 'academic';
    case ATTENDANCE = 'attendance';
    case FINANCE = 'finance';
    case LIBRARY = 'library';
    case CLINIC = 'clinic';
    case GUIDANCE = 'guidance';
    case INVENTORY = 'inventory';
    case REPORTS = 'reports';
    case ANALYTICS = 'analytics';
    case PARENT_PORTAL = 'parent-portal';
    case STUDENT_PORTAL = 'student-portal';
    case TEACHER_PORTAL = 'teacher-portal';
    case NOTIFICATIONS = 'notifications';
    case ADVANCED_REPORTS = 'advanced-reports';
    case MULTI_CAMPUS = 'multi-campus';
    case API_ACCESS = 'api-access';
    case CUSTOM_BRANDING = 'custom-branding';

    public function label(): string
    {
        return match ($this) {
            self::STUDENTS => 'Students',
            self::ENROLLMENT => 'Enrollment',
            self::ACADEMIC => 'Academic',
            self::ATTENDANCE => 'Attendance',
            self::FINANCE => 'Finance',
            self::LIBRARY => 'Library',
            self::CLINIC => 'Clinic',
            self::GUIDANCE => 'Guidance',
            self::INVENTORY => 'Inventory',
            self::REPORTS => 'Reports',
            self::ANALYTICS => 'Analytics',
            self::PARENT_PORTAL => 'Parent Portal',
            self::STUDENT_PORTAL => 'Student Portal',
            self::TEACHER_PORTAL => 'Teacher Portal',
            self::NOTIFICATIONS => 'Notifications',
            self::ADVANCED_REPORTS => 'Advanced Reports',
            self::MULTI_CAMPUS => 'Multi-Campus',
            self::API_ACCESS => 'API Access',
            self::CUSTOM_BRANDING => 'Custom Branding',
        };
    }

    /**
     * Dropdown options for plan builders.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function toOptions(): array
    {
        return array_map(
            static fn (self $feature) => ['value' => $feature->value, 'label' => $feature->label()],
            self::cases()
        );
    }
}
