import type { RouteRecordRaw } from 'vue-router';
import { foundationRoutes } from './foundation/routes';

/**
 * Feature modules register their routes here.
 *
 * Each KONEXUS module (e.g. academics, finance, registrar) should export a
 * `RouteRecordRaw[]` from its own `routes.ts` and spread it below. Child
 * routes are nested under the authenticated `AppLayout` shell.
 */
export const moduleRoutes: RouteRecordRaw[] = [...foundationRoutes];
