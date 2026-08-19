import { STAFF_PORTALS } from '@/config/staffPortals';
import { ROLE_HOME_PATHS } from '@/lib/roles';
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
export type PlatformNavGroup = 'portals' | 'workspace' | 'administration' | 'platform';

export interface PlatformNavEntry {
    key: string;
    title: string;
    path: string;
    icon: LucideIcon;
    description: string;
    roles?: string[];
    group?: PlatformNavGroup;
    exact?: boolean;
}

export const PLATFORM_NAV: PlatformNavEntry[] = [
    {
        key: 'student-portal',
        title: 'Student Portal',
        path: '/portal/student',
        icon: GraduationCap,
        description: 'Your profile, grades and schedule.',
        roles: ['student', 'super-administrator', 'school-administrator'],
        group: 'portals',
    },
    {
        key: 'parent-portal',
        title: 'Parent Portal',
        path: '/portal/parent',
        icon: HeartHandshake,
        description: 'Follow your children.',
        roles: ['parent', 'super-administrator', 'school-administrator'],
        group: 'portals',
    },
    {
        key: 'teacher-portal',
        title: 'Teacher Portal',
        path: '/portal/teacher',
        icon: Users,
        description: 'Your classes and schedule.',
        roles: ['teacher', 'adviser', 'super-administrator', 'school-administrator'],
        group: 'portals',
    },
    ...STAFF_PORTALS.map((portal) => ({
        key: `staff-portal-${portal.role}`,
        title: portal.label,
        path: ROLE_HOME_PATHS[portal.role] ?? `/portal/staff/${portal.role}`,
        icon: portal.icon,
        description: portal.description,
        roles: [portal.role, 'super-administrator', 'school-administrator'],
        group: 'portals' as const,
    })),
    {
        key: 'notifications',
        title: 'Notification Center',
        path: '/notifications',
        icon: Bell,
        description: 'Everything happening around you.',
        group: 'workspace',
    },
    {
        key: 'admin-dashboard',
        title: 'Admin Dashboard',
        path: '/admin/dashboard',
        icon: BarChart3,
        description: 'Operational analytics.',
        roles: ['super-administrator', 'school-administrator'],
        group: 'administration',
    },
    {
        key: 'activity-logs',
        title: 'Audit & Activity',
        path: '/admin/activity',
        icon: Activity,
        description: 'The full audit trail.',
        roles: ['super-administrator', 'school-administrator'],
        group: 'administration',
    },
    {
        key: 'users',
        title: 'Users & Roles',
        path: '/admin/users',
        icon: UserCog,
        description: 'Accounts, roles and access.',
        roles: ['super-administrator', 'school-administrator'],
        group: 'administration',
    },
    {
        key: 'settings',
        title: 'Settings',
        path: '/admin/settings',
        icon: Settings,
        description: 'Grouped system configuration.',
        roles: ['super-administrator', 'school-administrator'],
        group: 'administration',
    },
    {
        key: 'reports',
        title: 'Reports',
        path: '/admin/reports',
        icon: Megaphone,
        description: 'CSV and PDF exports.',
        roles: ['super-administrator', 'school-administrator'],
        group: 'administration',
    },
    {
        key: 'maintenance',
        title: 'Maintenance',
        path: '/admin/maintenance',
        icon: HardDrive,
        description: 'System health and backups.',
        roles: ['super-administrator'],
        group: 'administration',
    },
    {
        key: 'subscription-overview',
        title: 'Subscription Overview',
        path: '/admin/subscription',
        icon: ChartNoAxesCombined,
        description: 'Revenue, tenants and lifecycle health.',
        roles: ['super-administrator', 'platform-administrator'],
        group: 'platform',
        exact: true,
    },
    {
        key: 'subscription-tenants',
        title: 'Tenants',
        path: '/admin/subscription/tenants',
        icon: Building2,
        description: 'Organizations provisioned on the platform.',
        roles: ['super-administrator', 'platform-administrator'],
        group: 'platform',
    },
    {
        key: 'subscription-plans',
        title: 'Plans',
        path: '/admin/subscription/plans',
        icon: Boxes,
        description: 'Tiers, pricing and feature sets.',
        roles: ['super-administrator', 'platform-administrator'],
        group: 'platform',
    },
    {
        key: 'subscriptions',
        title: 'Subscriptions',
        path: '/admin/subscription/subscriptions',
        icon: RadioTower,
        description: 'Provision and manage subscriptions.',
        roles: ['super-administrator', 'platform-administrator'],
        group: 'platform',
    },
    {
        key: 'subscription-billing',
        title: 'Billing',
        path: '/admin/subscription/billing',
        icon: Banknote,
        description: 'Invoices and payments.',
        roles: ['super-administrator', 'platform-administrator'],
        group: 'platform',
    },
];
