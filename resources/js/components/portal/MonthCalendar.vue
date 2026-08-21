<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';

export interface CalendarEventLike {
    id: number;
    title: string;
    category?: string | null;
    start_date: string | null;
    end_date?: string | null;
    location?: string | null;
}

const props = defineProps<{
    events: CalendarEventLike[];
}>();

const MONTH_LABELS = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const WEEKDAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const today = new Date();
const cursorYear = ref(today.getFullYear());
const cursorMonth = ref(today.getMonth());

const monthLabel = computed(() => `${MONTH_LABELS[cursorMonth.value]} ${cursorYear.value}`);

function toKey(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function shiftMonth(delta: number): void {
    const next = new Date(cursorYear.value, cursorMonth.value + delta, 1);
    cursorYear.value = next.getFullYear();
    cursorMonth.value = next.getMonth();
}

function goToday(): void {
    cursorYear.value = today.getFullYear();
    cursorMonth.value = today.getMonth();
}

interface CalendarCell {
    key: string;
    day: number;
    inMonth: boolean;
    isToday: boolean;
}

const cells = computed<CalendarCell[]>(() => {
    const firstOfMonth = new Date(cursorYear.value, cursorMonth.value, 1);
    // Monday-first offset (Mon=0 … Sun=6).
    const leading = (firstOfMonth.getDay() + 6) % 7;

    const gridStart = new Date(cursorYear.value, cursorMonth.value, 1 - leading);

    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + index);
        return {
            key: toKey(date),
            day: date.getDate(),
            inMonth: date.getMonth() === cursorMonth.value,
            isToday: toKey(date) === toKey(today),
        };
    });
});

const eventsByDate = computed<Map<string, CalendarEventLike[]>>(() => {
    const map = new Map<string, CalendarEventLike[]>();
    for (const event of props.events) {
        if (!event.start_date) continue;
        const end = event.end_date || event.start_date;
        const startDate = new Date(`${event.start_date}T00:00:00`);
        const endDate = new Date(`${end}T00:00:00`);

        for (const cell of cells.value) {
            if (!cell.inMonth) continue;
            const cellDate = new Date(`${cell.key}T00:00:00`);
            if (cellDate >= startDate && cellDate <= endDate) {
                const list = map.get(cell.key) ?? [];
                list.push(event);
                map.set(cell.key, list);
            }
        }
    }
    return map;
});

function visibleEvents(key: string): CalendarEventLike[] {
    return (eventsByDate.value.get(key) ?? []).slice(0, 3);
}

function hiddenCount(key: string): number {
    return Math.max(0, (eventsByDate.value.get(key)?.length ?? 0) - 3);
}

const CATEGORY_TONES: Record<string, string> = {
    holiday: 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-400',
    exam: 'border-orange-500/30 bg-orange-500/10 text-orange-700 dark:text-orange-400',
    examination: 'border-orange-500/30 bg-orange-500/10 text-orange-700 dark:text-orange-400',
    activity: 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-400',
    event: 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-400',
    meeting: 'border-violet-500/30 bg-violet-500/10 text-violet-700 dark:text-violet-400',
};

function toneFor(category?: string | null): string {
    return CATEGORY_TONES[(category ?? '').toLowerCase()] ?? 'border-primary/20 bg-primary/8 text-primary';
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-border/60 bg-card/60">
        <header class="flex items-center justify-between border-b border-border/60 px-4 py-3">
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="icon" aria-label="Previous month" @click="shiftMonth(-1)">
                    <ChevronLeft class="size-4" />
                </Button>
                <Button variant="ghost" size="icon" aria-label="Next month" @click="shiftMonth(1)">
                    <ChevronRight class="size-4" />
                </Button>
            </div>
            <p class="font-display text-base font-medium tracking-[-0.01em]">{{ monthLabel }}</p>
            <Button variant="outline" size="sm" @click="goToday">Today</Button>
        </header>

        <div class="grid grid-cols-7 border-b border-border/60 bg-muted/40">
            <div
                v-for="label in WEEKDAY_LABELS"
                :key="label"
                class="px-2 py-2 text-center font-mono text-[10px] uppercase tracking-[0.14em] text-muted-foreground"
            >
                {{ label }}
            </div>
        </div>

        <div class="grid grid-cols-7">
            <div
                v-for="cell in cells"
                :key="cell.key"
                class="min-h-20 border-b border-r border-border/40 p-1.5 last:border-r-0 sm:min-h-24"
                :class="[!cell.inMonth ? 'bg-muted/20' : '', cell.isToday ? 'bg-primary/5' : '']"
            >
                <div class="flex items-center justify-between px-0.5">
                    <span
                        class="inline-flex size-5 items-center justify-center rounded-full text-[11px]"
                        :class="[
                            cell.isToday ? 'bg-primary font-semibold text-primary-foreground' : '',
                            !cell.inMonth && !cell.isToday ? 'text-muted-foreground/50' : 'text-muted-foreground',
                        ]"
                    >
                        {{ cell.day }}
                    </span>
                </div>

                <div v-if="visibleEvents(cell.key).length" class="mt-1 space-y-1">
                    <p
                        v-for="event in visibleEvents(cell.key)"
                        :key="`${cell.key}-${event.id}`"
                        class="truncate rounded border px-1 py-0.5 text-[10px] leading-tight"
                        :class="toneFor(event.category)"
                        :title="event.title"
                    >
                        {{ event.title }}
                    </p>
                    <p v-if="hiddenCount(cell.key)" class="px-1 text-[10px] text-muted-foreground">+{{ hiddenCount(cell.key) }} more</p>
                </div>
            </div>
        </div>
    </div>
</template>
