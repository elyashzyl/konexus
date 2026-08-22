<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenuButton } from '@/components/ui/sidebar';
import { useAuthStore } from '@/stores/auth';
import { useWorkspaceStore } from '@/stores/workspace';
import type { CampusWorkspace } from '@/types';
import { Building2, Check, ChevronDown, GraduationCap, LoaderCircle, MapPin, Settings2 } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

const auth = useAuthStore();
const workspace = useWorkspaceStore();
const router = useRouter();

const isSuperAdmin = computed(() => auth.can('super-administrator'));
const canManageCampuses = computed(() => auth.can('school-administrator') || auth.can('super-administrator'));
const schoolName = computed(() => workspace.activeCampus?.school_profile?.name ?? auth.user?.school?.name ?? 'School workspace');
const activeCampusId = computed(() => String(workspace.activeCampus?.id ?? ''));

interface WorkspaceGroup {
    id: number;
    name: string;
    campuses: CampusWorkspace[];
}

// Campuses arrive scoped to the account; group them by school so one selector
// can switch both the school and its campus workspace.
const groups = computed<WorkspaceGroup[]>(() => {
    const map = new Map<number, WorkspaceGroup>();
    const order: number[] = [];

    for (const campus of workspace.campuses) {
        const schoolId = campus.school_profile_id;

        let group = map.get(schoolId);

        if (!group) {
            group = {
                id: schoolId,
                name: campus.school_profile?.name ?? 'School workspace',
                campuses: [],
            };
            map.set(schoolId, group);
            order.push(schoolId);
        }

        group.campuses.push(campus);
    }

    return order.map((id) => map.get(id)!).sort((a, b) => a.name.localeCompare(b.name));
});

async function changeWorkspace(value: string): Promise<void> {
    const campusId = Number(value);
    if (!campusId || campusId === workspace.activeCampus?.id) {
        return;
    }

    try {
        await workspace.select(campusId);
        toast.success(`Switched to ${workspace.activeCampus?.name}.`);
        await router.replace({ path: router.currentRoute.value.path, query: { ...router.currentRoute.value.query } });
    } catch {
        toast.error('We could not change the campus workspace. Please try again.');
    }
}

onMounted(() => {
    if (!workspace.initialized) {
        void workspace.initialize();
    }
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <SidebarMenuButton
                size="lg"
                class="h-auto min-h-14 justify-between gap-2 rounded-xl border border-sidebar-border bg-sidebar-accent/45 px-3.5 py-3 hover:bg-sidebar-accent/45 active:bg-sidebar-accent/45 data-[state=open]:bg-sidebar-accent/45 data-[state=open]:hover:bg-sidebar-accent/45"
            >
                <span class="flex min-w-0 items-center gap-2.5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm">
                        <Building2 class="size-4" />
                    </span>
                    <span class="min-w-0 text-left">
                        <span class="block truncate text-[11px] font-medium uppercase tracking-[0.12em] text-muted-foreground">{{ schoolName }}</span>
                        <span class="block truncate text-sm font-semibold">{{ workspace.activeCampus?.name ?? 'Choose a campus' }}</span>
                    </span>
                </span>
                <LoaderCircle v-if="workspace.loading" class="size-4 shrink-0 animate-spin text-muted-foreground" />
                <ChevronDown v-else class="size-4 shrink-0 text-muted-foreground" />
            </SidebarMenuButton>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="start" class="w-80 rounded-xl p-2">
            <DropdownMenuLabel class="px-2 py-2">
                <p class="text-sm font-semibold">Workspace</p>
                <p class="mt-0.5 text-xs font-normal text-muted-foreground">Pick a school and its campus workspace.</p>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <DropdownMenuRadioGroup :model-value="activeCampusId" @update:model-value="changeWorkspace">
                <template v-for="group in groups" :key="group.id">
                    <div v-if="groups.length > 1" class="mt-2 flex items-center gap-1.5 px-2 py-1 first:mt-0">
                        <GraduationCap class="size-3 text-muted-foreground" />
                        <span class="truncate text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">{{ group.name }}</span>
                    </div>
                    <DropdownMenuRadioItem
                        v-for="campus in group.campuses"
                        :key="campus.id"
                        :value="String(campus.id)"
                        class="my-1 min-h-14 rounded-lg py-2.5 pl-9"
                    >
                        <span class="min-w-0">
                            <span class="flex items-center gap-2">
                                <span class="truncate font-medium">{{ campus.name }}</span>
                                <span v-if="campus.code" class="rounded bg-muted px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{{ campus.code }}</span>
                            </span>
                            <span class="mt-0.5 flex items-center gap-1 truncate text-xs text-muted-foreground">
                                <MapPin class="size-3 shrink-0" />
                                {{ campus.address || campus.school_profile?.name || 'Campus profile configured' }}
                            </span>
                        </span>
                        <Check v-if="campus.id === workspace.activeCampus?.id" class="ml-auto size-4 text-primary" />
                    </DropdownMenuRadioItem>
                </template>
                <div v-if="groups.length === 0" class="px-3 py-6 text-center text-xs text-muted-foreground">
                    No campus workspaces available for this account.
                </div>
            </DropdownMenuRadioGroup>

            <DropdownMenuSeparator />
            <DropdownMenuItem v-if="canManageCampuses" as-child class="mt-1 cursor-pointer rounded-lg py-2">
                <RouterLink to="/school/campuses">
                    <Settings2 class="size-4" />
                    Manage workspaces
                </RouterLink>
            </DropdownMenuItem>
            <DropdownMenuItem v-if="isSuperAdmin" as-child class="cursor-pointer rounded-lg py-2">
                <RouterLink to="/school/profile">
                    <GraduationCap class="size-4" />
                    Manage schools
                </RouterLink>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>