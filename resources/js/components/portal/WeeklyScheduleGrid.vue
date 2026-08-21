<script setup lang="ts">
import type { PortalScheduleEntry } from '@/types/platform';
import { computed } from 'vue';

const props = defineProps<{
    entries: PortalScheduleEntry[];
    /** Optional secondary line under each subject (e.g. teacher or room). */
    detail?: 'teacher' | 'room';
}>();

const DAYS = [
    { key: 'Monday', short: 'Mon' },
    { key: 'Tuesday', short: 'Tue' },
    { key: 'Wednesday', short: 'Wed' },
    { key: 'Thursday', short: 'Thu' },
    { key: 'Friday', short: 'Fri' },
    { key: 'Saturday', short: 'Sat' },
    { key: 'Sunday', short: 'Sun' },
];

function timeKey(value: string | null): string {
    return value ?? '';
}

const byDay = computed<Record<string, PortalScheduleEntry[]>>(() => {
    const grouped: Record<string, PortalScheduleEntry[]> = {};
    for (const day of DAYS) {
        grouped[day.key] = [];
    }
    for (const entry of props.entries) {
        if (grouped[entry.day]) {
            grouped[entry.day].push(entry);
        }
    }
    for (const day of Object.keys(grouped)) {
        grouped[day].sort((a, b) => timeKey(a.start_time).localeCompare(timeKey(b.start_time)));
    }
    return grouped;
});

const busiest = computed(() => Math.max(1, ...DAYS.map((day) => byDay.value[day.key]?.length ?? 0)));

function formatTime(value: string | null): string {
    if (!value) return '';
    const [hours, minutes] = value.split(':');
    const hour = Number(hours);
    if (Number.isNaN(hour)) return value;
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const display = hour % 12 === 0 ? 12 : hour % 12;
    return `${display}:${minutes ?? '00'} ${suffix}`;
}
</script>

<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
        <section
            v-for="day in DAYS"
            :key="day.key"
            class="flex min-h-32 flex-col overflow-hidden rounded-2xl border border-border/60 bg-card/50"
        >
            <header class="flex items-center justify-between border-b border-border/60 px-3 py-2">
                <p class="text-sm font-semibold text-foreground">{{ day.short }}</p>
                <span v-if="byDay[day.key]?.length" class="font-mono text-[10px] text-muted-foreground">{{ byDay[day.key].length }}</span>
            </header>

            <div class="flex flex-1 flex-col gap-2 p-2">
                <template v-if="byDay[day.key]?.length">
                    <article
                        v-for="entry in byDay[day.key]"
                        :key="entry.id"
                        class="rounded-xl border border-primary/15 bg-primary/5 px-2.5 py-2"
                    >
                        <p class="font-mono text-[10px] font-medium uppercase tracking-wide text-primary">
                            {{ formatTime(entry.start_time) }}{{ entry.end_time ? ` – ${formatTime(entry.end_time)}` : '' }}
                        </p>
                        <p class="mt-0.5 truncate text-[13px] font-medium leading-tight text-foreground" :title="entry.subject ?? ''">
                            {{ entry.subject ?? '—' }}
                        </p>
                        <p class="mt-0.5 truncate text-[11px] text-muted-foreground">
                            {{ detail === 'room' ? (entry.room ?? '') : (entry.teacher ?? '') }}
                        </p>
                    </article>
                </template>
                <div
                    v-else
                    class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-border/60"
                    :style="{ minHeight: `${busiest * 3.25}rem` }"
                >
                    <span class="text-[11px] text-muted-foreground/60">No classes</span>
                </div>
            </div>
        </section>
    </div>
</template>
