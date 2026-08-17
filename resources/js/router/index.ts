import { APP_NAME, APP_ROUTES, AUTH_ROUTES } from '@/constants/app';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { homePathForRoles, isAdmin } from '@/lib/roles';
import { moduleRoutes, portalRoutes } from '@/modules';
import { FOUNDATION_MODULES } from '@/modules/foundation/config';
import { useAuthStore } from '@/stores/auth';
import { useWorkspaceStore } from '@/stores/workspace';
import type { RouteRecordRaw } from 'vue-router';
import { createRouter, createWebHistory } from 'vue-router';

// Remembers the last visited authenticated page so that a fresh app load
// returns the user there instead of the dashboard/landing "start".
const LAST_ROUTE_STORAGE_KEY = 'konexus_last_route';

function getStoredLastPath(): string | null {
    try {
        return localStorage.getItem(LAST_ROUTE_STORAGE_KEY);
    } catch {
        return null;
    }
}

function storeLastPath(path: string): void {
    try {
        localStorage.setItem(LAST_ROUTE_STORAGE_KEY, path);
    } catch {
        // Ignore storage failures (e.g. private browsing mode).
    }
}

const isStartPath = (path: string): boolean => path === APP_ROUTES.dashboard.path || path === APP_ROUTES.landing.path;

let isInitialNavigation = true;

// Keeps the scroll position per visited page for the duration of the tab
// session, so navigating away and back (or reloading) doesn't reset the page.
const SCROLL_POSITIONS_KEY = 'konexus_scroll_positions';

type ScrollPositions = Record<string, number>;

function readScrollPositions(): ScrollPositions {
    try {
        const raw = sessionStorage.getItem(SCROLL_POSITIONS_KEY);
        return raw ? (JSON.parse(raw) as ScrollPositions) : {};
    } catch {
        return {};
    }
}

function writeScrollPositions(positions: ScrollPositions): void {
    try {
        sessionStorage.setItem(SCROLL_POSITIONS_KEY, JSON.stringify(positions));
    } catch {
        // Ignore storage failures (e.g. private browsing mode).
    }
}

const pageKey = (fullPath: string): string => fullPath.split('#')[0];

function saveScrollPosition(path: string, top: number): void {
    const positions = readScrollPositions();
    positions[pageKey(path)] = top;
    writeScrollPositions(positions);
}

export function restoreScrollPosition(path: string): number | null {
    return readScrollPositions()[pageKey(path)] ?? null;
}

function persistCurrentScroll(): void {
    saveScrollPosition(window.location.pathname + window.location.search, window.scrollY);
}

// The browser's own reload scroll restoration would fight our per-page restore.
if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}

// Reserves vertical space below the app so the saved position can be reached
// before the routed content has rendered. Without it the browser clamps the
// scroll to the top and the later jump back down looks like a glitch.
const SCROLL_SPACER_SELECTOR = '[data-scroll-spacer]';

function removeScrollSpacer(): void {
    document.querySelector(SCROLL_SPACER_SELECTOR)?.remove();
}

function addScrollSpacer(top: number): void {
    removeScrollSpacer();
    const spacer = document.createElement('div');
    spacer.setAttribute('data-scroll-spacer', '');
    spacer.style.height = `${top + window.innerHeight}px`;
    document.body.appendChild(spacer);
}

export function prepareScrollRestore(): void {
    const storedTop = restoreScrollPosition(window.location.pathname + window.location.search);

    if (storedTop !== null && storedTop > 0) {
        addScrollSpacer(storedTop);
        lastProgrammaticScrollAt = Date.now();
        window.scrollTo({ top: storedTop, behavior: 'auto' });
    }
}

let lastProgrammaticScrollAt = 0;
let userScrolled = false;
let restoreTimeoutId: number | undefined;

function clearPendingScrollRestores(): void {
    if (restoreTimeoutId !== undefined) {
        window.clearTimeout(restoreTimeoutId);
        restoreTimeoutId = undefined;
    }
}

// Restores the saved position immediately (a spacer guarantees it isn't
// clamped), then waits for the real content to be tall enough to remove the
// spacer without jumping.
function restoreScrollAfterPaint(top: number): void {
    clearPendingScrollRestores();
    userScrolled = false;
    lastProgrammaticScrollAt = Date.now();
    addScrollSpacer(top);
    window.scrollTo({ top, behavior: 'auto' });
    const deadline = Date.now() + 4000;

    const attempt = (): void => {
        if (userScrolled) {
            removeScrollSpacer();
            return;
        }

        const spacer = document.querySelector(SCROLL_SPACER_SELECTOR);
        const spacerHeight = spacer instanceof HTMLElement ? spacer.offsetHeight : 0;
        const realMaxScroll = document.documentElement.scrollHeight - spacerHeight - window.innerHeight;

        if (realMaxScroll >= top - 2) {
            removeScrollSpacer();

            if (Math.abs(window.scrollY - top) > 2) {
                lastProgrammaticScrollAt = Date.now();
                window.scrollTo({ top, behavior: 'auto' });
            }
            return;
        }

        if (Date.now() < deadline) {
            restoreTimeoutId = window.setTimeout(attempt, 200);
        } else {
            removeScrollSpacer();
        }
    };

    attempt();
}

{
    let timer: number | undefined;

    window.addEventListener(
        'scroll',
        () => {
            if (Date.now() - lastProgrammaticScrollAt > 300) {
                userScrolled = true;
                clearPendingScrollRestores();
                removeScrollSpacer();

                if (timer !== undefined) {
                    window.clearTimeout(timer);
                }
                timer = window.setTimeout(persistCurrentScroll, 200);
            }
        },
        { passive: true },
    );

    window.addEventListener('pagehide', persistCurrentScroll);
}

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: APP_ROUTES.landing.name,
        component: () => import('@/pages/Landing.vue'),
        meta: { title: APP_NAME, requiresGuest: true },
    },
    {
        path: '/enrollment',
        name: APP_ROUTES.enrollment.name,
        component: () => import('@/pages/Enrollment.vue'),
        meta: { title: 'Online Enrollment' },
    },
    {
        path: '/dashboard',
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: APP_ROUTES.dashboard.name,
                component: () => import('@/pages/Dashboard.vue'),
                meta: {
                    title: 'Dashboard',
                    breadcrumbs: [{ title: 'Dashboard', href: APP_ROUTES.dashboard.path }],
                },
            },
            {
                path: '/settings',
                component: SettingsLayout,
                meta: { requiresAuth: true },
                children: [
                    {
                        path: 'profile',
                        name: APP_ROUTES.settings.profile.name,
                        component: () => import('@/pages/settings/Profile.vue'),
                        meta: { title: 'Profile' },
                    },
                    {
                        path: 'password',
                        name: APP_ROUTES.settings.password.name,
                        component: () => import('@/pages/settings/Password.vue'),
                        meta: { title: 'Password' },
                    },
                    {
                        path: 'appearance',
                        name: APP_ROUTES.settings.appearance.name,
                        component: () => import('@/pages/settings/Appearance.vue'),
                        meta: { title: 'Appearance' },
                    },
                ],
            },
            ...moduleRoutes,
        ],
    },
    ...portalRoutes,
    {
        path: '/auth',
        component: AuthLayout,
        meta: { requiresGuest: true },
        children: [
            {
                path: 'login',
                name: AUTH_ROUTES.login.name,
                component: () => import('@/pages/auth/Login.vue'),
                meta: { title: 'Log in', description: 'Welcome back — sign in to your school portal.' },
            },
            {
                path: 'register',
                name: AUTH_ROUTES.register.name,
                component: () => import('@/pages/auth/Register.vue'),
                meta: { title: 'Create an account', description: 'Join KONEXUS and get started in minutes.' },
            },
            {
                path: 'forgot-password',
                name: AUTH_ROUTES['forgot-password'].name,
                component: () => import('@/pages/auth/ForgotPassword.vue'),
                meta: { title: 'Forgot password', description: "Enter your email and we'll send you a reset link." },
            },
            {
                path: 'reset-password',
                name: AUTH_ROUTES['reset-password'].name,
                component: () => import('@/pages/auth/ResetPassword.vue'),
                meta: { title: 'Reset password', description: 'Choose a new password for your account.' },
            },
            {
                path: 'social/callback',
                name: AUTH_ROUTES['social-callback'].name,
                component: () => import('@/pages/auth/SocialAuthCallback.vue'),
                meta: { title: 'Completing sign-in' },
            },
        ],
    },
    {
        path: '/403',
        name: APP_ROUTES.errors[403].name,
        component: () => import('@/pages/errors/ErrorPage.vue'),
        props: {
            status: 403,
            title: 'Forbidden',
            description: 'You do not have permission to access this resource. Contact your administrator if you believe this is a mistake.',
        },
    },
    {
        path: '/404',
        name: APP_ROUTES.errors[404].name,
        component: () => import('@/pages/errors/ErrorPage.vue'),
        props: {
            status: 404,
            title: 'Page not found',
            description: 'The page you are looking for does not exist or has been moved.',
        },
    },
    {
        path: '/500',
        name: APP_ROUTES.errors[500].name,
        component: () => import('@/pages/errors/ErrorPage.vue'),
        props: {
            status: 500,
            title: 'Something went wrong',
            description: 'An unexpected error occurred on our servers. Please try again later.',
        },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: APP_ROUTES.errors[404].path,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, _from, savedPosition) {
        if (savedPosition) {
            lastProgrammaticScrollAt = Date.now();
            return savedPosition;
        }

        const storedTop = restoreScrollPosition(to.fullPath);

        if (storedTop !== null && storedTop > 0) {
            lastProgrammaticScrollAt = Date.now();
            return { top: storedTop, behavior: 'auto' };
        }

        if (to.hash) {
            return { el: to.hash, behavior: 'smooth' };
        }

        return { top: 0 };
    },
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.initialized) {
        await auth.initialize();
    }

    if (auth.isAuthenticated) {
        const workspace = useWorkspaceStore();
        await workspace.initialize();
    }

    if (auth.isAuthenticated && isInitialNavigation && isStartPath(to.path)) {
        const lastPath = getStoredLastPath();

        if (lastPath && lastPath !== to.fullPath && !lastPath.startsWith('/auth')) {
            isInitialNavigation = false;
            return lastPath;
        }
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return {
            name: AUTH_ROUTES.login.name,
            query: to.fullPath !== APP_ROUTES.dashboard.path ? { redirect: to.fullPath } : {},
        };
    }

    if (to.meta.requiresGuest && auth.isAuthenticated) {
        return { path: homePathForRoles(auth.user?.roles) };
    }

    const registrarEnrollmentRoute = auth.can('registrar') && to.path.startsWith('/school/enrollments');

    if (
        auth.isAuthenticated &&
        !isAdmin(auth.user?.roles) &&
        !to.path.startsWith('/portal/') &&
        to.path !== APP_ROUTES.enrollment.path &&
        !registrarEnrollmentRoute
    ) {
        const home = homePathForRoles(auth.user?.roles);

        if (home && to.path !== home) {
            return { path: home };
        }
    }

    if (auth.isAuthenticated && to.meta.requiresAcademicOffice && !auth.can('school-administrator') && !auth.can('super-administrator')) {
        return { path: homePathForRoles(auth.user?.roles) };
    }

    if (auth.isAuthenticated && to.path.startsWith('/admin/subscription')) {
        const names = (auth.user?.roles ?? []).map((role) => role.name);
        const isPlatformOperator = names.includes('super-administrator') || names.includes('platform-administrator');

        if (!isPlatformOperator) {
            return { path: homePathForRoles(auth.user?.roles) };
        }
    }

    if (auth.isAuthenticated && isAdmin(auth.user?.roles) && !auth.can('school-administrator') && !auth.can('super-administrator')) {
        const foundationPaths = FOUNDATION_MODULES.map((module) => module.path);

        if (foundationPaths.some((path) => to.path.startsWith(path))) {
            return { path: homePathForRoles(auth.user?.roles) };
        }
    }

    return true;
});

router.afterEach((to) => {
    isInitialNavigation = false;
    clearPendingScrollRestores();
    const title = to.meta.title;

    document.title = title ? `${title} - ${APP_NAME}` : APP_NAME;

    if (to.meta.requiresAuth) {
        storeLastPath(to.fullPath);
    }

    const storedTop = restoreScrollPosition(to.fullPath);

    if (storedTop !== null && storedTop > 0) {
        restoreScrollAfterPaint(storedTop);
    } else {
        removeScrollSpacer();
    }
});

export default router;
