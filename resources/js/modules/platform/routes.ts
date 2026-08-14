import PortalLayout from '@/layouts/portal/PortalLayout.vue';
import type { RouteRecordRaw } from 'vue-router';

/**
 * Top-level portal routes. Each portal is rendered inside its own PortalLayout
 * (AppShell + role-aware PortalSidebar) instead of the global app sidebar, so
 * students, parents and teachers get a dedicated portal shell like the
 * reference site.
 */
export const portalRoutes: RouteRecordRaw[] = [
    {
        path: '/portal/student',
        component: PortalLayout,
        meta: { requiresAuth: true, portalRole: 'student', title: 'Student Portal' },
        children: [
            {
                path: '',
                name: 'portal.student',
                component: () => import('@/pages/portal/student/Overview.vue'),
                meta: { title: 'Student Portal', breadcrumbs: [{ title: 'Student Portal', href: '/portal/student' }] },
            },
            {
                path: 'grades',
                name: 'portal.student.grades',
                component: () => import('@/pages/portal/student/Grades.vue'),
                meta: { title: 'Grades', breadcrumbs: [{ title: 'Student Portal', href: '/portal/student' }, { title: 'Grades' }] },
            },
            {
                path: 'schedule',
                name: 'portal.student.schedule',
                component: () => import('@/pages/portal/student/Schedule.vue'),
                meta: { title: 'Schedule', breadcrumbs: [{ title: 'Student Portal', href: '/portal/student' }, { title: 'Schedule' }] },
            },
            {
                path: 'enrollments',
                name: 'portal.student.enrollments',
                component: () => import('@/pages/portal/student/Enrollments.vue'),
                meta: {
                    title: 'Enrollment History',
                    breadcrumbs: [{ title: 'Student Portal', href: '/portal/student' }, { title: 'Enrollment History' }],
                },
            },
            {
                path: 'documents',
                name: 'portal.student.documents',
                component: () => import('@/pages/portal/student/Documents.vue'),
                meta: { title: 'Documents', breadcrumbs: [{ title: 'Student Portal', href: '/portal/student' }, { title: 'Documents' }] },
            },
            {
                path: 'announcements',
                name: 'portal.student.announcements',
                component: () => import('@/pages/portal/student/Announcements.vue'),
                meta: { title: 'Announcements', breadcrumbs: [{ title: 'Student Portal', href: '/portal/student' }, { title: 'Announcements' }] },
            },
        ],
    },
    {
        path: '/portal/parent',
        component: PortalLayout,
        meta: { requiresAuth: true, portalRole: 'parent', title: 'Parent Portal' },
        children: [
            {
                path: '',
                name: 'portal.parent',
                component: () => import('@/pages/portal/parent/Overview.vue'),
                meta: { title: 'Parent Portal', breadcrumbs: [{ title: 'Parent Portal', href: '/portal/parent' }] },
            },
            {
                path: 'children/:id',
                name: 'portal.parent.child',
                component: () => import('@/pages/portal/parent/child/Overview.vue'),
                meta: { title: 'Child', breadcrumbs: [{ title: 'Parent Portal', href: '/portal/parent' }, { title: 'Child' }] },
            },
            {
                path: 'children/:id/grades',
                name: 'portal.parent.child.grades',
                component: () => import('@/pages/portal/parent/child/Grades.vue'),
                meta: { title: 'Grades', breadcrumbs: [{ title: 'Parent Portal', href: '/portal/parent' }, { title: 'Grades' }] },
            },
            {
                path: 'children/:id/schedule',
                name: 'portal.parent.child.schedule',
                component: () => import('@/pages/portal/parent/child/Schedule.vue'),
                meta: { title: 'Schedule', breadcrumbs: [{ title: 'Parent Portal', href: '/portal/parent' }, { title: 'Schedule' }] },
            },
            {
                path: 'children/:id/enrollments',
                name: 'portal.parent.child.enrollments',
                component: () => import('@/pages/portal/parent/child/Enrollments.vue'),
                meta: {
                    title: 'Enrollment History',
                    breadcrumbs: [{ title: 'Parent Portal', href: '/portal/parent' }, { title: 'Enrollment History' }],
                },
            },
            {
                path: 'children/:id/documents',
                name: 'portal.parent.child.documents',
                component: () => import('@/pages/portal/parent/child/Documents.vue'),
                meta: { title: 'Documents', breadcrumbs: [{ title: 'Parent Portal', href: '/portal/parent' }, { title: 'Documents' }] },
            },
        ],
    },
    {
        path: '/portal/staff/:role',
        component: PortalLayout,
        meta: { requiresAuth: true, portalRole: 'staff', title: 'Staff Portal' },
        children: [
            {
                path: '',
                name: 'portal.staff',
                component: () => import('@/pages/portal/staff/Overview.vue'),
                meta: { title: 'Staff Portal', breadcrumbs: [{ title: 'Staff Portal' }] },
            },
            {
                path: 'announcements',
                name: 'portal.staff.announcements',
                component: () => import('@/pages/portal/staff/Announcements.vue'),
                meta: { title: 'Announcements', breadcrumbs: [{ title: 'Staff Portal' }, { title: 'Announcements' }] },
            },
        ],
    },
    {
        path: '/portal/teacher',
        component: PortalLayout,
        meta: { requiresAuth: true, portalRole: 'teacher', title: 'Teacher Portal' },
        children: [
            {
                path: '',
                name: 'portal.teacher',
                component: () => import('@/pages/portal/teacher/Overview.vue'),
                meta: { title: 'Teacher Portal', breadcrumbs: [{ title: 'Teacher Portal', href: '/portal/teacher' }] },
            },
            {
                path: 'classes',
                name: 'portal.teacher.classes',
                component: () => import('@/pages/portal/teacher/Classes.vue'),
                meta: { title: 'My Classes', breadcrumbs: [{ title: 'Teacher Portal', href: '/portal/teacher' }, { title: 'My Classes' }] },
            },
            {
                path: 'schedule',
                name: 'portal.teacher.schedule',
                component: () => import('@/pages/portal/teacher/Schedule.vue'),
                meta: { title: 'Schedule', breadcrumbs: [{ title: 'Teacher Portal', href: '/portal/teacher' }, { title: 'Schedule' }] },
            },
            {
                path: 'advisory',
                name: 'portal.teacher.advisory',
                component: () => import('@/pages/portal/teacher/Advisory.vue'),
                meta: { title: 'Advisory', breadcrumbs: [{ title: 'Teacher Portal', href: '/portal/teacher' }, { title: 'Advisory' }] },
            },
        ],
    },
];

/**
 * App-shell routes (notifications + admin) stay nested under the global
 * AppLayout sidebar, rendered from the authenticated `/` shell.
 */
export const platformRoutes: RouteRecordRaw[] = [
    {
        path: '/notifications',
        name: 'platform.notifications',
        component: () => import('@/pages/notifications/Index.vue'),
        meta: { title: 'Notification Center', breadcrumbs: [{ title: 'Notification Center', href: '/notifications' }] },
    },
    {
        path: '/admin/dashboard',
        name: 'platform.admin.dashboard',
        component: () => import('@/pages/admin/Dashboard.vue'),
        meta: { title: 'Admin Dashboard', breadcrumbs: [{ title: 'Admin Dashboard', href: '/admin/dashboard' }] },
    },
    {
        path: '/admin/activity',
        name: 'platform.admin.activity',
        component: () => import('@/pages/admin/ActivityLogs.vue'),
        meta: { title: 'Audit & Activity', breadcrumbs: [{ title: 'Audit & Activity', href: '/admin/activity' }] },
    },
    {
        path: '/admin/users',
        name: 'platform.admin.users',
        component: () => import('@/pages/admin/Users.vue'),
        meta: { title: 'Users & Roles', breadcrumbs: [{ title: 'Users & Roles', href: '/admin/users' }] },
    },
    {
        path: '/admin/settings',
        name: 'platform.admin.settings',
        component: () => import('@/pages/admin/Settings.vue'),
        meta: { title: 'Settings', breadcrumbs: [{ title: 'Settings', href: '/admin/settings' }] },
    },
    {
        path: '/admin/reports',
        name: 'platform.admin.reports',
        component: () => import('@/pages/admin/Reports.vue'),
        meta: { title: 'Reports', breadcrumbs: [{ title: 'Reports', href: '/admin/reports' }] },
    },
    {
        path: '/admin/maintenance',
        name: 'platform.admin.maintenance',
        component: () => import('@/pages/admin/Maintenance.vue'),
        meta: { title: 'Maintenance', breadcrumbs: [{ title: 'Maintenance', href: '/admin/maintenance' }] },
    },
    {
        path: '/admin/subscription',
        name: 'platform.admin.subscription.overview',
        component: () => import('@/pages/admin/subscription/Overview.vue'),
        meta: { title: 'Subscription Overview', breadcrumbs: [{ title: 'Subscription Overview', href: '/admin/subscription' }] },
    },
    {
        path: '/admin/subscription/tenants',
        name: 'platform.admin.subscription.tenants',
        component: () => import('@/pages/admin/subscription/Tenants.vue'),
        meta: { title: 'Tenants', breadcrumbs: [{ title: 'Subscription', href: '/admin/subscription' }, { title: 'Tenants' }] },
    },
    {
        path: '/admin/subscription/plans',
        name: 'platform.admin.subscription.plans',
        component: () => import('@/pages/admin/subscription/Plans.vue'),
        meta: { title: 'Plans', breadcrumbs: [{ title: 'Subscription', href: '/admin/subscription' }, { title: 'Plans' }] },
    },
    {
        path: '/admin/subscription/subscriptions',
        name: 'platform.admin.subscription.subscriptions',
        component: () => import('@/pages/admin/subscription/Subscriptions.vue'),
        meta: { title: 'Subscriptions', breadcrumbs: [{ title: 'Subscription', href: '/admin/subscription' }, { title: 'Subscriptions' }] },
    },
    {
        path: '/admin/subscription/billing',
        name: 'platform.admin.subscription.billing',
        component: () => import('@/pages/admin/subscription/Billing.vue'),
        meta: { title: 'Billing', breadcrumbs: [{ title: 'Subscription', href: '/admin/subscription' }, { title: 'Billing' }] },
    },
];
