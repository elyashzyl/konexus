import type { RouteRecordRaw } from 'vue-router';
import { foundationRoutes } from './foundation/routes';
import { portalRoutes as platformPortalRoutes, platformRoutes } from './platform/routes';

/**
 * Feature modules register their routes here.
 *
 * Each KONEXUS module (e.g. academics, finance, registrar) should export a
 * `RouteRecordRaw[]` from its own `routes.ts` and spread it below. Child
 * routes are nested under the authenticated `AppLayout` shell, while portal
 * routes render inside their own `PortalLayout` (top-level).
 */
export const moduleRoutes: RouteRecordRaw[] = [...foundationRoutes, ...platformRoutes];

export const portalRoutes: RouteRecordRaw[] = platformPortalRoutes;
