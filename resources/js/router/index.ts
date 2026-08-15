import { APP_NAME, APP_ROUTES, AUTH_ROUTES } from '@/constants/app';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { moduleRoutes, portalRoutes } from '@/modules';
import { FOUNDATION_MODULES } from '@/modules/foundation/config';
import { useAuthStore } from '@/stores/auth';
import { homePathForRoles, isAdmin } from '@/lib/roles';
import type { RouteRecordRaw } from 'vue-router';
import { createRouter, createWebHistory } from 'vue-router';

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
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.initialized) {
        await auth.initialize();
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

    if (auth.isAuthenticated && !isAdmin(auth.user?.roles) && !to.path.startsWith('/portal/') && to.path !== APP_ROUTES.enrollment.path) {
        const home = homePathForRoles(auth.user?.roles);

        if (home && to.path !== home) {
            return { path: home };
        }
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
    const title = to.meta.title;

    document.title = title ? `${title} - ${APP_NAME}` : APP_NAME;
});

export default router;
