<script setup lang="ts">
import WeeklyScheduleGrid from '@/components/portal/WeeklyScheduleGrid.vue';
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
                    <span v-if="schedule.length" class="inline-flex items-center gap-2.5 rounded-full border border-primary/15 bg-primary/6 px-4 py-1.5 font-mono text-xs text-primary">
                        <span class="index-num">{{ schedule.length }}</span> entries
                    </span>
                </template>
            </PortalPageHeader>
        </div>

        <div v-if="loading" class="mt-12 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            <div v-for="i in 7" :key="i" class="h-40 animate-pulse rounded-2xl bg-muted/60" />
        </div>

        <div v-else-if="!schedule.length" class="mt-12">
            <PortalEmptyState
                :icon="CalendarDays"
                index="02"
                title="No schedule yet"
                description="This child's class schedule will appear here once it has been published."
            />
        </div>

        <WeeklyScheduleGrid v-else class="portal-rise mt-12" :entries="schedule" detail="teacher" />
    </main>
</template>
