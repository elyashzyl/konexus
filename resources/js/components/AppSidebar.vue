<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import WorkspaceSwitcher from '@/components/WorkspaceSwitcher.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { APP_ROUTES } from '@/constants/app';
import { FOUNDATION_MODULES } from '@/modules/foundation/config';
import { PLATFORM_NAV } from '@/modules/platform/config';
import { useAuthStore } from '@/stores/auth';
import { type NavItem } from '@/types';
import { BookOpen, Folder, LayoutGrid, PanelTop } from 'lucide-vue-next';
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import AppLogo from './AppLogo.vue';

const auth = useAuthStore();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: APP_ROUTES.dashboard.path,
        icon: LayoutGrid,
    },
];

const canManageSchool = computed(() => auth.can('school-administrator') || auth.can('super-administrator'));

function moduleNavItem(key: string, title?: string): NavItem | null {
    const module = FOUNDATION_MODULES.find((entry) => entry.key === key);

    return module
        ? {
              title: title ?? module.title,
              href: module.path,
              icon: module.icon,
          }
        : null;
}

function configuredNavItems(entries: Array<[key: string, title?: string]>): NavItem[] {
    return entries.map(([key, title]) => moduleNavItem(key, title)).filter((item): item is NavItem => item !== null);
}

const academicNavItems = computed<NavItem[]>(() =>
    canManageSchool.value
        ? [
              {
                  title: 'Academic Operations',
                  href: '/academic-operations',
                  icon: PanelTop,
              },
          ]
        : [],
);

const enrollmentNavItems = computed<NavItem[]>(() =>
    canManageSchool.value
        ? configuredNavItems([
              ['enrollments', 'Enrollment records'],
              ['students', 'Learner directory'],
          ])
        : [],
);

const academicSetupNavItems = computed<NavItem[]>(() =>
    canManageSchool.value
        ? configuredNavItems([['academic-years', 'School years & terms'], ['grade-levels'], ['sections'], ['subjects'], ['departments']])
        : [],
);

const schoolSetupNavItems = computed<NavItem[]>(() =>
    canManageSchool.value
        ? configuredNavItems([['school-profile'], ['campuses'], ['buildings'], ['rooms'], ['school-calendar'], ['announcements']])
        : [],
);

const platformNavItems = computed<NavItem[]>(() =>
    PLATFORM_NAV.filter((entry) => !entry.roles || entry.roles.some((role) => auth.can(role))).map((entry) => ({
        title: entry.title,
        href: entry.path,
        icon: entry.icon,
    })),
);

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <RouterLink :to="APP_ROUTES.dashboard.path">
                            <AppLogo />
                        </RouterLink>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <WorkspaceSwitcher />
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Platform" />
            <NavMain v-if="platformNavItems.length > 0" :items="platformNavItems" label="Portals & Admin" />
            <NavMain v-if="academicNavItems.length > 0" :items="academicNavItems" label="Academic Office" />
            <NavMain v-if="enrollmentNavItems.length > 0" :items="enrollmentNavItems" label="Admissions & Records" />
            <NavMain v-if="academicSetupNavItems.length > 0" :items="academicSetupNavItems" label="Academic Setup" />
            <NavMain v-if="schoolSetupNavItems.length > 0" :items="schoolSetupNavItems" label="School Setup" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
