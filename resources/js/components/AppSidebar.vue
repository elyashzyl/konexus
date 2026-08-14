<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { APP_ROUTES } from '@/constants/app';
import { FOUNDATION_MODULES } from '@/modules/foundation/config';
import { PLATFORM_NAV } from '@/modules/platform/config';
import { useAuthStore } from '@/stores/auth';
import { type NavItem } from '@/types';
import { BookOpen, Folder, LayoutGrid } from 'lucide-vue-next';
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

const schoolNavItems = computed<NavItem[]>(() =>
    auth.can('school-administrator')
        ? FOUNDATION_MODULES.map((module) => ({
              title: module.title,
              href: module.path,
              icon: module.icon,
          }))
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
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Platform" />
            <NavMain v-if="platformNavItems.length > 0" :items="platformNavItems" label="Portals & Admin" />
            <NavMain v-if="schoolNavItems.length > 0" :items="schoolNavItems" label="School" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
