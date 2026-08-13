<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { extractError } from '@/lib/api';
import { portalApi } from '@/lib/portalApi';
import type { PortalScheduleEntry } from '@/types/platform';
import { ArrowLeft, CalendarDays } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue-sonner';

const route = useRoute();
const childId = computed(() => Number(route.params.id));

const loading = ref(true);
const schedule = ref<PortalScheduleEntry[]>([]);

onMounted(async () => {
    try {
        schedule.value = await portalApi.parent.schedule(childId.value);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const sortedSchedule = computed(() => [...schedule.value].sort((a, b) => dayOrder.indexOf(a.day) - dayOrder.indexOf(b.day)));

function pad(index: number): string {
    return String(index + 1).padStart(2, '0');
}
</script>

<template>
    <main class="w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12 lg:pt-16">
        <RouterLink
            :to="`/portal/parent/children/${childId}`"
            class="portal-rise inline-flex items-center gap-2 px-2 text-sm font-medium text-primary hover:underline"
        >
            <ArrowLeft class="size-4" />
            Child overview
        </RouterLink>

        <div class="mt-8">
            <PortalPageHeader
                :icon="CalendarDays"
                eyebrow="Academics"
                index="02"
                title="Weekly schedule"
                description="The timetable for the current term, as published by the registrar."
            >
                <template #actions>
                    <span v-if="sortedSchedule.length" class="inline-flex items-center gap-2.5 rounded-full border border-primary/15 bg-primary/6 px-4 py-1.5 font-mono text-xs text-primary">
                        <span class="index-num">{{ sortedSchedule.length }}</span> entries
                    </span>
                </template>
            </PortalPageHeader>
        </div>

        <div v-if="loading" class="mt-12 space-y-3">
            <div v-for="i in 4" :key="i" class="h-12 animate-pulse rounded-xl bg-muted/60" />
        </div>

        <div v-else-if="!sortedSchedule.length" class="mt-12">
            <PortalEmptyState
                :icon="CalendarDays"
                index="02"
                title="No schedule yet"
                description="This child's class schedule will appear here once it has been published."
            />
        </div>

        <div v-else class="portal-rise mt-12 divide-y divide-border/60 border-y border-border/60">
            <div v-for="(entry, index) in sortedSchedule" :key="entry.id" class="flex items-center gap-4 py-4">
                <span class="index-num w-7 shrink-0 font-mono text-xs text-muted-foreground/60">{{ pad(index) }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[15px] font-medium text-foreground">{{ entry.subject }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ entry.teacher }}</p>
                </div>
                <span class="shrink-0 font-mono text-[11px] text-muted-foreground">
                    {{ entry.day }} · {{ entry.start_time }}–{{ entry.end_time }}
                </span>
            </div>
        </div>
    </main>
</template>
