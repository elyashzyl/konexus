import type { RouteRecordRaw } from 'vue-router';
import { FOUNDATION_MODULES } from './config';

/**
 * Routes for every Phase 2 foundation module. Most modules render the shared
 * CRUD page configured by `config.ts`; modules with a bespoke page (such as
 * School Profile) map to their own component.
 */
export const foundationRoutes: RouteRecordRaw[] = [
    ...FOUNDATION_MODULES.map((module) => {
        const isSchoolProfile = module.key === 'school-profile';
        const isCampusWorkspaces = module.key === 'campuses';

        return {
            path: module.path,
            name: `foundation.${module.key}`,
            component: isSchoolProfile
                ? () => import('@/pages/foundation/SchoolProfileView.vue')
                : isCampusWorkspaces
                  ? () => import('@/pages/foundation/CampusWorkspacesView.vue')
                  : () => import('@/pages/foundation/ModulePage.vue'),
            props: isSchoolProfile || isCampusWorkspaces ? undefined : { moduleKey: module.key },
            meta: {
                title: module.title,
                breadcrumbs: [{ title: module.title, href: module.path }],
            },
        };
    }),
    {
        path: '/school/enrollments/apply',
        name: 'foundation.enrollments.apply',
        component: () => import('@/pages/foundation/WalkInEnrollment.vue'),
        meta: {
            title: 'New Walk-in Enrollment',
            breadcrumbs: [
                { title: 'Enrollment Data', href: '/school/enrollments' },
                { title: 'New Walk-in Enrollment', href: '/school/enrollments/apply' },
            ],
        },
    },
];
