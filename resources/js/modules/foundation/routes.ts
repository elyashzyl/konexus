import type { RouteRecordRaw } from 'vue-router';
import { FOUNDATION_MODULES } from './config';

/**
 * Routes for every Phase 2 foundation module. Most modules render the shared
 * CRUD page configured by `config.ts`; modules with a bespoke page (such as
 * School Profile) map to their own component.
 */
export const foundationRoutes: RouteRecordRaw[] = FOUNDATION_MODULES.map((module) => {
    const isSchoolProfile = module.key === 'school-profile';

    return {
        path: module.path,
        name: `foundation.${module.key}`,
        component: isSchoolProfile
            ? () => import('@/pages/foundation/SchoolProfileView.vue')
            : () => import('@/pages/foundation/ModulePage.vue'),
        props: isSchoolProfile ? undefined : { moduleKey: module.key },
        meta: {
            title: module.title,
            breadcrumbs: [{ title: module.title, href: module.path }],
        },
    };
});