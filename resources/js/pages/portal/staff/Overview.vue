<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { staffPortalByRole } from '@/config/staffPortals';
import api, { extractError } from '@/lib/api';
import { useAuthStore } from '@/stores/auth';
import {
    BellRing,
    BookOpen,
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    CalendarRange,
    ClipboardCheck,
    ClipboardList,
    DoorOpen,
    FilePlus2,
    GraduationCap,
    LoaderCircle,
    MapPin,
    Megaphone,
    School,
    Users,
    Wallet,
} from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { toast } from 'vue-sonner';

type Stat = {
    key: string;
    label: string;
    value: number;
    href: string | null;
};

type Dashboard = {
    role: string | null;
    academic_year: string | null;
    campus: string | null;
    unread_notifications: number;
    stats: Stat[];
};

const STAT_ICONS: Record<string, Component> = {
    enrollments: ClipboardList,
    'officially-enrolled': GraduationCap,
    'for-review': ClipboardCheck,
    drafts: FilePlus2,
    students: Users,
    approvals: School,
    payments: Wallet,
    employees: BriefcaseBusiness,
    subjects: BookOpen,
    buildings: Building2,
    rooms: DoorOpen,
    announcements: Megaphone,
    events: CalendarDays,
};

const auth = useAuthStore();
const route = useRoute();

const loading = ref(true);
const dashboard = ref<Dashboard | null>(null);

const portal = computed(() => {
    const roleParam = route.params.role as string | undefined;

    return staffPortalByRole(roleParam ?? dashboard.value?.role ?? auth.primaryRole?.name ?? 'principal');
});

const portalBase = computed(() => {
    const match = route.path.match(/^\/portal\/staff\/[^/]+/);

    return match ? match[0] : `/portal/staff/${portal.value?.role ?? 'registrar'}`;
});

function iconFor(key: string): Component {
    return STAT_ICONS[key] ?? ClipboardList;
}

async function load(): Promise<void> {
    loading.value = true;

    try {
        const response = await api.get<{ data: Dashboard }>('/portal/staff/dashboard');

        dashboard.value = response.data.data;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    void load();
});
</script>

<template>
    <div class="relative">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 sm:px-8 lg:px-12">
            <section class="portal-rise flex flex-col justify-between gap-6 pt-10 lg:flex-row lg:items-end lg:pt-16">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="bg-primary/8 flex size-9 items-center justify-center rounded-lg text-primary ring-1 ring-primary/10">
                            <BriefcaseBusiness class="size-4" />
                        </span>
                        <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">{{ portal?.eyebrow }}</p>
                    </div>

                    <h1 class="mt-6 font-display text-4xl font-medium leading-tight tracking-[-0.02em] text-foreground sm:text-5xl">
                        {{ portal?.label }} at a glance
                    </h1>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Badge v-if="dashboard?.academic_year" variant="outline" class="gap-1.5 px-3 py-1.5 text-xs font-medium">
                        <CalendarRange class="size-3.5 text-primary" />
                        AY {{ dashboard.academic_year }}
                    </Badge>
                    <Badge v-if="dashboard?.campus" variant="outline" class="gap-1.5 px-3 py-1.5 text-xs font-medium">
                        <MapPin class="size-3.5 text-primary" />
                        {{ dashboard.campus }}
                    </Badge>
                    <Badge variant="outline" class="gap-1.5 px-3 py-1.5 text-xs font-medium">
                        <BellRing class="size-3.5 text-primary" />
                        {{ dashboard?.unread_notifications ?? 0 }} unread
                    </Badge>
                </div>
            </section>

            <div v-if="loading && !dashboard" class="portal-rise mt-14 flex items-center justify-center gap-2 text-sm text-muted-foreground">
                <LoaderCircle class="size-4 animate-spin" />
                Loading office statistics…
            </div>

            <template v-else-if="dashboard">
                <section class="portal-rise mt-10" style="animation-delay: 120ms">
                    <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground">Office statistics</p>

                    <div
                        class="mt-5 grid gap-4"
                        :class="dashboard.stats.length >= 5 ? 'sm:grid-cols-2 lg:grid-cols-5' : 'sm:grid-cols-2 lg:grid-cols-4'"
                    >
                        <component
                            :is="stat.href ? RouterLink : 'div'"
                            v-for="stat in dashboard.stats"
                            :key="stat.key"
                            :to="stat.href ? `${portalBase}/${stat.href}` : undefined"
                            class="group rounded-2xl border border-border/60 bg-card p-6 transition-colors"
                            :class="stat.href ? 'hover:border-primary/30' : ''"
                        >
                            <div class="flex items-start justify-between">
                                <span class="bg-primary/8 flex size-10 items-center justify-center rounded-xl text-primary ring-1 ring-primary/10">
                                    <component :is="iconFor(stat.key)" class="size-5" />
                                </span>
                                <span
                                    class="font-display text-4xl font-medium tabular-nums tracking-[-0.02em] text-foreground"
                                >{{ stat.value }}</span>
                            </div>

                            <p class="mt-5 text-sm leading-5 text-muted-foreground">{{ stat.label }}</p>
                        </component>
                    </div>
                </section>

                <footer class="portal-rise mt-14 border-t border-border/60 pt-6" style="animation-delay: 240ms">
                    <div class="flex flex-col justify-between gap-3 text-xs text-muted-foreground sm:flex-row sm:items-center">
                        <p>Counts refresh each time you open this page.</p>
                        <p>Records are maintained by the school office.</p>
                    </div>
                </footer>
            </template>
        </div>
    </div>
</template>
