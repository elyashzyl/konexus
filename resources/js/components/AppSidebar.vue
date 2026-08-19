<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import WorkspaceSwitcher from '@/components/WorkspaceSwitcher.vue';
import { Input } from '@/components/ui/input';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { buildAdminNav, filterAdminNav } from '@/config/adminNav';
import { APP_ROUTES } from '@/constants/app';
import { useAuthStore } from '@/stores/auth';
import { useStorage } from '@vueuse/core';
import { Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import AppLogo from './AppLogo.vue';

const auth = useAuthStore();
const { state, isMobile } = useSidebar();
const isIconMode = computed(() => state.value === 'collapsed' && !isMobile.value);

const query = ref('');
const openState = useStorage<Record<string, boolean>>('konexus:sidebar-groups', {});

const navigation = computed(() => buildAdminNav(auth.can));
const visibleNavigation = computed(() => filterAdminNav(navigation.value, query.value));
const hasResults = computed(() => visibleNavigation.value.pinned.length > 0 || visibleNavigation.value.groups.length > 0);

function clearQuery(): void {
    query.value = '';
}
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
            <div v-if="!isIconMode" class="px-2 pb-1">
                <label class="relative block">
                    <Search class="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-sidebar-foreground/50" />
                    <Input
                        v-model="query"
                        type="text"
                        placeholder="Search pages…"
                        class="h-8 border-sidebar-border bg-sidebar-accent/40 pl-8 pr-8 text-sm shadow-none placeholder:text-sidebar-foreground/50 focus-visible:ring-sidebar-ring"
                    />
                    <button
                        v-if="query"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-sm p-0.5 text-sidebar-foreground/50 hover:text-sidebar-foreground"
                        aria-label="Clear search"
                        @click="clearQuery"
                    >
                        <X class="size-3.5" />
                    </button>
                </label>
            </div>

            <p v-if="query && !hasResults && !isIconMode" class="px-4 py-6 text-center text-xs text-muted-foreground">No pages match “{{ query }}”</p>

            <NavMain v-else v-model:open-state="openState" :pinned="visibleNavigation.pinned" :groups="visibleNavigation.groups" :query="query" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
