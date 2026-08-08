import type { RouteRecordRaw } from 'vue-router';
import { FOUNDATION_MODULES } from './config';

/**
 * Routes for every Phase 2 foundation module. Each route renders the shared
 * CRUD page configured by `config.ts`.
 */
export const foundationRoutes: RouteRecordRaw[] = FOUNDATION_MODULES.map((module) => ({
    path: module.path,
    name: `foundation.${module.key}`,
    component: () => import('@/pages/foundation/ModulePage.vue'),
    props: { moduleKey: module.key },
    meta: {
        title: module.title,
        breadcrumbs: [{ title: module.title, href: module.path }],
    },
}));
