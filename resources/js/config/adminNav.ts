import { APP_ROUTES } from '@/constants/app';
import { FOUNDATION_MODULES } from '@/modules/foundation/config';
import { PLATFORM_NAV, type PlatformNavEntry, type PlatformNavGroup } from '@/modules/platform/config';
import type { LucideIcon } from 'lucide-vue-next';
import { BookOpen, Building2, GraduationCap, LayoutGrid, PanelTop, School, Settings2, Users } from 'lucide-vue-next';

export interface AdminNavLink {
    title: string;
    href: string;
    icon?: LucideIcon;
    exact?: boolean;
    heading?: string;
}

export interface AdminNavGroup {
    key: string;
    title: string;
    icon: LucideIcon;
    items: AdminNavLink[];
    separatorBefore?: boolean;
}

export interface AdminNavModel {
    pinned: AdminNavLink[];
    groups: AdminNavGroup[];
}

const COMMUNITY_PORTAL_PATHS = ['/portal/student', '/portal/parent', '/portal/teacher'];

const PORTAL_TITLES: Record<string, string> = {
    'student-portal': 'Student',
    'parent-portal': 'Parent',
    'teacher-portal': 'Teacher',
};

const ADMIN_TITLES: Record<string, string> = {
    'admin-dashboard': 'Overview',
    'activity-logs': 'Activity',
    users: 'Users',
};

const PLATFORM_TITLES: Record<string, string> = {
    'subscription-overview': 'Overview',
    'subscription-tenants': 'Tenants',
    'subscription-plans': 'Plans',
    'subscription-billing': 'Billing',
};

export function isActivePath(current: string, href: string, exact = false): boolean {
    if (exact || href === '/') {
        return current === href;
    }

    return current === href || current.startsWith(`${href}/`);
}

function moduleLink(key: string, title?: string): AdminNavLink | null {
    const module = FOUNDATION_MODULES.find((entry) => entry.key === key);

    return module
        ? {
              title: title ?? module.title,
              href: module.path,
              icon: module.icon,
          }
        : null;
}

function moduleLinks(entries: Array<[key: string, title?: string]>): AdminNavLink[] {
    return entries.map(([key, title]) => moduleLink(key, title)).filter((item): item is AdminNavLink => item !== null);
}

function canSee(entry: PlatformNavEntry, can: (role: string) => boolean): boolean {
    return !entry.roles || entry.roles.some((role) => can(role));
}

function platformLinks(group: PlatformNavGroup, can: (role: string) => boolean, titles: Record<string, string> = {}): AdminNavLink[] {
    return PLATFORM_NAV.filter((entry) => entry.group === group && canSee(entry, can)).map((entry) => ({
        title: titles[entry.key] ?? entry.title,
        href: entry.path,
        icon: entry.icon,
        exact: entry.exact,
    }));
}

export function buildAdminNav(can: (role: string) => boolean): AdminNavModel {
    const canManageSchool = can('school-administrator') || can('super-administrator');

    const pinned: AdminNavLink[] = [
        { title: 'Dashboard', href: APP_ROUTES.dashboard.path, icon: LayoutGrid, exact: true },
        ...platformLinks('workspace', can, { notifications: 'Notifications' }),
    ];

    const groups: AdminNavGroup[] = [];

    if (canManageSchool) {
        const people = moduleLinks([
            ['students', 'Students'],
            ['enrollments', 'Enrollments'],
            ['tuitions', 'Tuition'],
        ]);

        if (people.length > 0) {
            groups.push({ key: 'people', title: 'People & records', icon: GraduationCap, items: people, separatorBefore: true });
        }

        const academics: AdminNavLink[] = [
            { title: 'Operations', href: '/academic-operations', icon: PanelTop },
            ...moduleLinks([
                ['academic-years', 'Academic years'],
                ['academic-terms', 'Academic terms'],
                ['grade-levels', 'Grade levels'],
                ['sections', 'Sections'],
                ['subjects', 'Subjects'],
                ['departments', 'Departments'],
            ]),
        ];

        groups.push({ key: 'academics', title: 'Academics', icon: BookOpen, items: academics });

        const campus = moduleLinks([
            ['school-profile', 'School profile'],
            ['campuses', 'Campuses'],
            ['buildings', 'Buildings'],
            ['rooms', 'Rooms'],
            ['school-calendar', 'Calendar'],
            ['announcements', 'Announcements'],
        ]);

        if (campus.length > 0) {
            groups.push({ key: 'campus', title: 'Campus', icon: School, items: campus });
        }
    }

    const portalEntries = platformLinks('portals', can, PORTAL_TITLES);
    const community = portalEntries.filter((item) => COMMUNITY_PORTAL_PATHS.includes(item.href));
    const staff = portalEntries.filter((item) => item.href.startsWith('/portal/staff'));
    const portalItems: AdminNavLink[] = [...community, ...staff.map((item, index) => (index === 0 ? { ...item, heading: 'Staff offices' } : item))];

    if (portalItems.length > 0) {
        groups.push({
            key: 'portals',
            title: 'Portals',
            icon: Users,
            items: portalItems,
            separatorBefore: !canManageSchool,
        });
    }

    const administration = [
        ...platformLinks('administration', can, ADMIN_TITLES),
        ...(canManageSchool ? moduleLinks([['master-data', 'Master data']]) : []),
    ];

    if (administration.length > 0) {
        groups.push({ key: 'administration', title: 'Administration', icon: Settings2, items: administration, separatorBefore: true });
    }

    const platform = platformLinks('platform', can, PLATFORM_TITLES);

    if (platform.length > 0) {
        groups.push({ key: 'platform', title: 'Platform', icon: Building2, items: platform, separatorBefore: administration.length === 0 });
    }

    return { pinned, groups };
}

export function filterAdminNav(model: AdminNavModel, query: string): AdminNavModel {
    const term = query.trim().toLowerCase();

    if (!term) {
        return model;
    }

    const matches = (title: string): boolean => title.toLowerCase().includes(term);

    return {
        pinned: model.pinned.filter((item) => matches(item.title)),
        groups: model.groups
            .map((group) => ({
                ...group,
                items: matches(group.title) ? group.items : group.items.filter((item) => matches(item.title) || matches(item.heading ?? '')),
            }))
            .filter((group) => group.items.length > 0),
    };
}

export function groupContainsPath(group: AdminNavGroup, path: string): boolean {
    return group.items.some((item) => isActivePath(path, item.href, item.exact));
}
