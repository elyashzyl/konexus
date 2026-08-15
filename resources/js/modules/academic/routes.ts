import type { RouteRecordRaw } from 'vue-router';

export const academicRoutes: RouteRecordRaw[] = [
    {
        path: '/academic-operations',
        name: 'academic.operations',
        component: () => import('@/pages/academic/Operations.vue'),
        meta: {
            title: 'Academic Operations',
            requiresAcademicOffice: true,
            breadcrumbs: [{ title: 'Academic Operations', href: '/academic-operations' }],
        },
    },
];
