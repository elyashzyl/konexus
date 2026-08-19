<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    SidebarSeparator,
    useSidebar,
} from '@/components/ui/sidebar';
import { type AdminNavGroup, type AdminNavLink, groupContainsPath, isActivePath } from '@/config/adminNav';
import { ChevronRight } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

const props = defineProps<{
    pinned: AdminNavLink[];
    groups: AdminNavGroup[];
    query?: string;
}>();

const route = useRoute();
const { state, isMobile } = useSidebar();
const isIconMode = computed(() => state.value === 'collapsed' && !isMobile.value);
const searching = computed(() => Boolean(props.query?.trim()));

const openState = defineModel<Record<string, boolean>>('openState', { default: () => ({}) });

function isLinkActive(item: AdminNavLink): boolean {
    return isActivePath(route.path, item.href, item.exact);
}

function isGroupActive(group: AdminNavGroup): boolean {
    return groupContainsPath(group, route.path);
}

function isGroupOpen(group: AdminNavGroup): boolean {
    if (searching.value) {
        return true;
    }

    return openState.value[group.key] ?? false;
}

function setGroupOpen(key: string, open: boolean): void {
    openState.value = { ...openState.value, [key]: open };
}

watch(
    () => route.path,
    (path) => {
        const next = { ...openState.value };
        let changed = false;

        for (const group of props.groups) {
            if (groupContainsPath(group, path) && !next[group.key]) {
                next[group.key] = true;
                changed = true;
            }
        }

        if (changed) {
            openState.value = next;
        }
    },
    { immediate: true },
);
</script>

<template>
    <SidebarGroup v-if="pinned.length > 0" class="px-2 py-0">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in pinned" :key="item.href">
                <SidebarMenuButton as-child :is-active="isLinkActive(item)" :tooltip="item.title">
                    <RouterLink :to="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </RouterLink>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <template v-for="group in groups" :key="group.key">
        <SidebarSeparator v-if="group.separatorBefore" class="mx-2 my-1" />

        <SidebarGroup class="px-2 py-0">
            <SidebarMenu>
                <SidebarMenuItem v-if="isIconMode">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton :tooltip="group.title" :is-active="isGroupActive(group)">
                                <component :is="group.icon" />
                                <span>{{ group.title }}</span>
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent side="right" align="start" :side-offset="8" class="min-w-52 rounded-lg">
                            <DropdownMenuLabel class="text-xs text-muted-foreground">{{ group.title }}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <template v-for="item in group.items" :key="item.href">
                                <DropdownMenuLabel
                                    v-if="item.heading"
                                    class="px-2 pb-1 pt-2 text-[10px] font-medium uppercase tracking-[0.14em] text-muted-foreground"
                                >
                                    {{ item.heading }}
                                </DropdownMenuLabel>
                                <DropdownMenuItem as-child class="cursor-pointer gap-2">
                                    <RouterLink :to="item.href">
                                        <component :is="item.icon" class="text-muted-foreground" />
                                        <span>{{ item.title }}</span>
                                    </RouterLink>
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>

                <SidebarMenuItem v-else>
                    <Collapsible :open="isGroupOpen(group)" class="group/collapsible" @update:open="(open: boolean) => setGroupOpen(group.key, open)">
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton :is-active="isGroupActive(group) && !isGroupOpen(group)">
                                <component :is="group.icon" />
                                <span>{{ group.title }}</span>
                                <ChevronRight
                                    class="ml-auto size-4 text-sidebar-foreground/50 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <template v-for="item in group.items" :key="item.href">
                                    <li
                                        v-if="item.heading"
                                        class="px-2 pb-0.5 pt-2 text-[10px] font-medium uppercase tracking-[0.14em] text-sidebar-foreground/50"
                                    >
                                        {{ item.heading }}
                                    </li>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton as-child :is-active="isLinkActive(item)">
                                            <RouterLink :to="item.href">
                                                <component :is="item.icon" />
                                                <span>{{ item.title }}</span>
                                            </RouterLink>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </template>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </Collapsible>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </template>
</template>
