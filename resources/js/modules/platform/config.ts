import type { LucideIcon } from 'lucide-vue-next';
import {
    Activity,
    Banknote,
    BarChart3,
    Bell,
    Boxes,
    Building2,
    ChartNoAxesCombined,
    GraduationCap,
    HardDrive,
    HeartHandshake,
    Megaphone,
    RadioTower,
    Settings,
    UserCog,
    Users,
} from 'lucide-vue-next';

/**
 * The Part 8 platform navigation model.
 *
 * Every entry declares the roles allowed to see it, so the sidebar can render
 * a permission-aware navigation without hardcoding role checks in the layout.
 */
export interface PlatformNavEntry {
    key: string;
    title: string;
    path: string;
    icon: LucideIcon;
    description: string;
    roles?: string[];
}

export const PLATFORM_NAV: PlatformNavEntry[] = [
    {
        key: 'student-portal',
        title: 'Student Portal',
        path: '/portal/student',
        icon: GraduationCap,
        description: 'Your profile, grades and schedule.',
        roles: ['student'],
    },
    {
        key: 'parent-portal',
        title: 'Parent Portal',
        path: '/portal/parent',
        icon: HeartHandshake,
        description: 'Follow your children.',
        roles: ['parent'],
    },
    {
        key: 'teacher-portal',
        title: 'Teacher Portal',
        path: '/portal/teacher',
        icon: Users,
        description: 'Your classes and schedule.',
        roles: ['teacher', 'adviser'],
    },
    { key: 'notifications', title: 'Notification Center', path: '/notifications', icon: Bell, description: 'Everything happening around you.' },
    {
        key: 'admin-dashboard',
        title: 'Admin Dashboard',
        path: '/admin/dashboard',
        icon: BarChart3,
        description: 'Operational analytics.',
        roles: ['super-administrator', 'school-administrator'],
    },
    {
        key: 'activity-logs',
        title: 'Audit & Activity',
        path: '/admin/activity',
        icon: Activity,
        description: 'The full audit trail.',
        roles: ['super-administrator', 'school-administrator'],
    },
    {
        key: 'users',
        title: 'Users & Roles',
        path: '/admin/users',
        icon: UserCog,
        description: 'Accounts, roles and access.',
        roles: ['super-administrator', 'school-administrator'],
    },
    {
        key: 'settings',
        title: 'Settings',
        path: '/admin/settings',
        icon: Settings,
        description: 'Grouped system configuration.',
        roles: ['super-administrator', 'school-administrator'],
    },
    {
        key: 'reports',
        title: 'Reports',
        path: '/admin/reports',
        icon: Megaphone,
        description: 'CSV and PDF exports.',
        roles: ['super-administrator', 'school-administrator'],
    },
    {
        key: 'maintenance',
        title: 'Maintenance',
        path: '/admin/maintenance',
        icon: HardDrive,
        description: 'System health and backups.',
        roles: ['super-administrator'],
    },
    {
        key: 'subscription-overview',
        title: 'Subscription Overview',
        path: '/admin/subscription',
        icon: ChartNoAxesCombined,
        description: 'Revenue, tenants and lifecycle health.',
        roles: ['super-administrator', 'platform-administrator'],
    },
    {
        key: 'subscription-tenants',
        title: 'Tenants',
        path: '/admin/subscription/tenants',
        icon: Building2,
        description: 'Organizations provisioned on the platform.',
        roles: ['super-administrator', 'platform-administrator'],
    },
    {
        key: 'subscription-plans',
        title: 'Plans',
        path: '/admin/subscription/plans',
        icon: Boxes,
        description: 'Tiers, pricing and feature sets.',
        roles: ['super-administrator', 'platform-administrator'],
    },
    {
        key: 'subscriptions',
        title: 'Subscriptions',
        path: '/admin/subscription/subscriptions',
        icon: RadioTower,
        description: 'Provision and manage subscriptions.',
        roles: ['super-administrator', 'platform-administrator'],
    },
    {
        key: 'subscription-billing',
        title: 'Billing',
        path: '/admin/subscription/billing',
        icon: Banknote,
        description: 'Invoices and payments.',
        roles: ['super-administrator', 'platform-administrator'],
    },
];
