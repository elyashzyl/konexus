export const APP_NAME = import.meta.env.VITE_APP_NAME || 'KONEXUS';

export const BRAND = {
    name: 'KONEXUS',
    tagline: 'School Management Information System',
} as const;

export const AUTH_ROUTES = {
    login: { name: 'auth.login', path: '/auth/login' },
    register: { name: 'auth.register', path: '/auth/register' },
    'forgot-password': { name: 'auth.forgot-password', path: '/auth/forgot-password' },
    'reset-password': { name: 'auth.reset-password', path: '/auth/reset-password' },
} as const;

export const APP_ROUTES = {
    landing: { name: 'landing', path: '/' },
    dashboard: { name: 'dashboard', path: '/dashboard' },
    settings: {
        profile: { name: 'settings.profile', path: '/settings/profile' },
        password: { name: 'settings.password', path: '/settings/password' },
        appearance: { name: 'settings.appearance', path: '/settings/appearance' },
    },
    errors: {
        403: { name: 'errors.403', path: '/403' },
        404: { name: 'errors.404', path: '/404' },
        500: { name: 'errors.500', path: '/500' },
    },
} as const;
