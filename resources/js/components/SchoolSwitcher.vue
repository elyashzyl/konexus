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
import api, { extractError } from '@/lib/api';
import { useAuthStore } from '@/stores/auth';
import { useWorkspaceStore } from '@/stores/workspace';
import { Check, ChevronDown, GraduationCap, LoaderCircle, Settings2 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

interface SchoolOption {
    id: number;
    name: string;
    short_name: string | null;
}

const auth = useAuthStore();
const workspace = useWorkspaceStore();
const router = useRouter();

const schools = ref<SchoolOption[]>([]);
const loadingSchools = ref(false);

const isSuperAdmin = computed(() => auth.can('super-administrator'));
const currentSchoolId = computed(() => workspace.activeCampus?.school_profile_id ?? null);
const currentSchoolName = computed(() => {
    if (!currentSchoolId.value) return 'School workspace';
    return (
        schools.value.find((school) => school.id === currentSchoolId.value)?.name ??
        workspace.activeCampus?.school_profile?.name ??
        'School workspace'
    );
});

async function loadSchools(): Promise<void> {
    if (!isSuperAdmin.value || schools.value.length > 0) {
        return;
    }

    loadingSchools.value = true;

    try {
        const { data } = await api.get<{ data: { items: SchoolOption[] } }>('/school-profiles', {
            params: { per_page: 100 },
        });
        schools.value = data.data.items;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loadingSchools.value = false;
    }
}

async function switchSchool(schoolId: number): Promise<void> {
    if (schoolId === currentSchoolId.value) {
        return;
    }

    const targetCampus = workspace.campuses.find((campus) => campus.school_profile_id === schoolId && campus.is_active);

    if (!targetCampus) {
        toast.error('This school has no active campus workspace to switch into.');
        return;
    }

    try {
        await workspace.select(targetCampus.id);
        toast.success(`Switched to ${currentSchoolName.value}.`);
        await router.replace({ path: router.currentRoute.value.path, query: { ...router.currentRoute.value.query } });
    } catch {
        toast.error('We could not change the school. Please try again.');
    }
}

onMounted(async () => {
    await loadSchools();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <SidebarMenuButton
                size="sm"
                class="h-auto min-h-10 justify-between rounded-xl border border-dashed border-sidebar-border bg-transparent px-3 py-2 hover:bg-sidebar-accent"
            >
                <span class="flex min-w-0 items-center gap-2">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                        <GraduationCap class="size-3.5" />
                    </span>
                    <span class="min-w-0 text-left">
                        <span class="block truncate text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">School</span>
                        <span class="block truncate text-sm font-medium">{{ currentSchoolName }}</span>
                    </span>
                </span>
                <LoaderCircle v-if="loadingSchools" class="size-4 shrink-0 animate-spin text-muted-foreground" />
                <ChevronDown v-else class="size-4 shrink-0 text-muted-foreground" />
            </SidebarMenuButton>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="start" class="w-80 rounded-xl p-2">
            <DropdownMenuLabel class="px-2 py-2">
                <p class="text-sm font-semibold">Switch school</p>
                <p class="mt-0.5 text-xs font-normal text-muted-foreground">Moves you into the first active campus of the chosen school.</p>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <DropdownMenuRadioGroup
                :model-value="String(currentSchoolId ?? '')"
                @update:model-value="(value) => switchSchool(Number(value))"
            >
                <DropdownMenuRadioItem v-for="school in schools" :key="school.id" :value="String(school.id)" class="my-1 min-h-11 rounded-lg py-2 pl-9">
                    <span class="min-w-0">
                        <span class="flex items-center gap-2">
                            <span class="truncate font-medium">{{ school.name }}</span>
                            <span v-if="school.short_name" class="rounded bg-muted px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{{ school.short_name }}</span>
                        </span>
                        <span class="mt-0.5 block truncate text-xs text-muted-foreground">
                            <template v-if="workspace.campuses.some((campus) => campus.school_profile_id === school.id && campus.is_active)">
                                {{ workspace.campuses.filter((campus) => campus.school_profile_id === school.id && campus.is_active).length }} active campus workspace(s)
                            </template>
                            <template v-else>No active campus workspace</template>
                        </span>
                    </span>
                    <Check v-if="school.id === currentSchoolId" class="ml-auto size-4 text-primary" />
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>

            <DropdownMenuSeparator />
            <DropdownMenuItem as-child class="mt-1 cursor-pointer rounded-lg py-2">
                <RouterLink to="/school/profile">
                    <Settings2 class="size-4" />
                    Manage schools
                </RouterLink>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>