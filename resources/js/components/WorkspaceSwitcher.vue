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
import { Building2, Check, ChevronDown, LoaderCircle, MapPin, Settings2 } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

const auth = useAuthStore();
const workspace = useWorkspaceStore();
const router = useRouter();

const canManageCampuses = computed(() => auth.can('school-administrator') || auth.can('super-administrator'));
const schoolName = computed(() => workspace.activeCampus?.school_profile?.name ?? auth.user?.school?.name ?? 'School workspace');
const activeCampusId = computed(() => String(workspace.activeCampus?.id ?? ''));
const activeSchoolId = computed(() => workspace.activeCampus?.school_profile_id ?? auth.user?.school_profile_id ?? null);
const schoolCampuses = computed(() =>
    activeSchoolId.value === null
        ? workspace.campuses
        : workspace.campuses.filter((campus) => campus.school_profile_id === activeSchoolId.value),
);

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
            <SidebarMenuButton size="lg" class="h-auto min-h-14 justify-between rounded-xl border border-sidebar-border bg-sidebar-accent/45 px-3 py-2.5 hover:bg-sidebar-accent">
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
                <p class="text-sm font-semibold">Campus workspace</p>
                <p class="mt-0.5 text-xs font-normal text-muted-foreground">Campuses belong to {{ schoolName }}. Switch school to see another school's campuses.</p>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <DropdownMenuRadioGroup :model-value="activeCampusId" @update:model-value="changeWorkspace">
                <DropdownMenuRadioItem v-for="campus in schoolCampuses" :key="campus.id" :value="String(campus.id)" class="my-1 min-h-14 rounded-lg py-2.5 pl-9">
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
                <div v-if="schoolCampuses.length === 0" class="px-3 py-6 text-center text-xs text-muted-foreground">
                    No campuses configured for this school yet.
                </div>
            </DropdownMenuRadioGroup>

            <template v-if="canManageCampuses">
                <DropdownMenuSeparator />
                <DropdownMenuItem as-child class="mt-1 cursor-pointer rounded-lg py-2">
                    <RouterLink to="/school/campuses">
                        <Settings2 class="size-4" />
                        Manage workspaces
                    </RouterLink>
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
