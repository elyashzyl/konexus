<script setup lang="ts">
import MonthCalendar from '@/components/portal/MonthCalendar.vue';
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { extractError } from '@/lib/api';
import { platformApi } from '@/lib/platformApi';
import type { SchoolCalendarEventItem } from '@/types/platform';
import { CalendarDays, Clock, MapPin } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const events = ref<SchoolCalendarEventItem[]>([]);

onMounted(async () => {
    try {
        const response = await platformApi.calendarEvents.index({ per_page: 200 });
        events.value = (response.items ?? []).filter((event) => event.is_active);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

const todayKey = new Date().toLocaleDateString('en-CA');

const upcoming = computed(() =>
    [...events.value]
        .filter((event) => event.start_date && event.start_date >= todayKey)
        .sort((a, b) => (a.start_date ?? '').localeCompare(b.start_date ?? ''))
        .slice(0, 8),
);

function formatTime(value: string | null): string {
    if (!value) return '';
    const [hours, minutes] = value.split(':');
    const hour = Number(hours);
    if (Number.isNaN(hour)) return value;
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const display = hour % 12 === 0 ? 12 : hour % 12;
    return `${display}:${minutes ?? '00'} ${suffix}`;
}

function formatDate(value?: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-PH', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}
</script>

<template>
    <main class="w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12 lg:pt-16">
        <PortalPageHeader
            :icon="CalendarDays"
            eyebrow="School life"
            index="02"
            title="School calendar"
            description="Official dates, holidays, examinations and school activities for the year."
        >
            <template #actions>
                <span v-if="events.length" class="inline-flex items-center gap-2.5 rounded-full border border-primary/15 bg-primary/6 px-4 py-1.5 font-mono text-xs text-primary">
                    <span class="index-num">{{ events.length }}</span> events
                </span>
            </template>
        </PortalPageHeader>

        <div v-if="loading" class="mt-12 h-[28rem] animate-pulse rounded-2xl bg-muted/60" />

        <div v-else-if="!events.length" class="mt-12">
            <PortalEmptyState
                :icon="CalendarDays"
                index="02"
                title="No calendar events yet"
                description="School dates and activities will appear here once published."
            />
        </div>

        <template v-else>
            <MonthCalendar class="portal-rise mt-12 max-w-4xl" :events="events" />

            <section v-if="upcoming.length" class="portal-rise mt-10 max-w-4xl">
                <h2 class="font-display text-lg font-medium tracking-[-0.01em]">Upcoming</h2>
                <div class="mt-4 divide-y divide-border/60 border-y border-border/60">
                    <article v-for="event in upcoming" :key="event.id" class="flex flex-wrap items-start gap-x-6 gap-y-1 py-3.5">
                        <p class="min-w-40 font-mono text-xs text-muted-foreground">
                            {{ formatDate(event.start_date) }}<template v-if="event.end_date && event.end_date !== event.start_date"> – {{ formatDate(event.end_date) }}</template>
                        </p>
                        <div class="min-w-0 flex-1">
                            <p class="text-[15px] font-medium text-foreground">{{ event.title }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-0.5 text-xs text-muted-foreground">
                                <span v-if="!event.all_day && event.start_time" class="inline-flex items-center gap-1.5">
                                    <Clock class="size-3.5" />
                                    {{ formatTime(event.start_time) }}<template v-if="event.end_time"> – {{ formatTime(event.end_time) }}</template>
                                </span>
                                <span v-if="event.location" class="inline-flex items-center gap-1.5">
                                    <MapPin class="size-3.5" />
                                    {{ event.location }}
                                </span>
                                <span v-if="event.category" class="rounded-full border border-border bg-muted/50 px-2 py-0.5 capitalize">{{ event.category }}</span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </template>
    </main>
</template>
