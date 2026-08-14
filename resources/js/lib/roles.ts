import type { Role } from '@/types';

export const ADMIN_ROLES = ['super-administrator', 'school-administrator', 'platform-administrator'];

export const STAFF_ROLES = [
    'principal',
    'registrar',
    'finance-officer',
    'guidance-counselor',
    'school-nurse',
    'librarian',
    'hr-officer',
    'inventory-officer',
];

const ROLE_ORDER = ['student', 'parent', 'teacher', 'adviser', ...STAFF_ROLES, ...ADMIN_ROLES] as const;

export const ROLE_HOME_PATHS: Record<string, string> = {
    student: '/portal/student',
    parent: '/portal/parent',
    teacher: '/portal/teacher',
    adviser: '/portal/teacher',
    principal: '/portal/staff/principal',
    registrar: '/portal/staff/registrar',
    'finance-officer': '/portal/staff/finance-officer',
    'guidance-counselor': '/portal/staff/guidance-counselor',
    'school-nurse': '/portal/staff/school-nurse',
    librarian: '/portal/staff/librarian',
    'hr-officer': '/portal/staff/hr-officer',
    'inventory-officer': '/portal/staff/inventory-officer',
    'super-administrator': '/dashboard',
    'school-administrator': '/dashboard',
    'platform-administrator': '/admin/subscription',
};

/**
 * Pick the landing page for a user based on their roles. Portal roles and
 * staff roles are sent straight to their portal; only admins land on the
 * dashboard (which holds the School + Admin pages).
 */
export function homePathForRoles(roles?: Role[]): string {
    if (!roles || roles.length === 0) {
        return '/dashboard';
    }

    const names = roles.map((role) => role.name);

    for (const role of ROLE_ORDER) {
        if (names.includes(role) && ROLE_HOME_PATHS[role]) {
            return ROLE_HOME_PATHS[role];
        }
    }

    return '/dashboard';
}

export function isAdmin(roles?: Role[]): boolean {
    if (!roles) return false;

    return roles.some((role) => ADMIN_ROLES.includes(role.name));
}
