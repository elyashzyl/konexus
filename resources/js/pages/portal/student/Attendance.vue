<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { extractError } from '@/lib/api';
import { portalApi } from '@/lib/portalApi';
import type { AttendanceSummary } from '@/types/platform';
import { CalendarCheck2, CircleCheck, CircleX, Clock3, HandHeart, Landmark } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const summary = ref<Partial<AttendanceSummary>>({});

const entries = computed(() => [
    {
        key: 'present',
        label: 'Present',
        description: 'Submitted sessions marked present',
        icon: CircleCheck,
        value: summary.value.present ?? 0,
        tone: 'text-emerald-700 bg-emerald-50 border-emerald-100',
    },
    {
        key: 'late',
        label: 'Late',
        description: 'Submitted sessions marked late',
        icon: Clock3,
        value: summary.value.late ?? 0,
        tone: 'text-amber-700 bg-amber-50 border-amber-100',
    },
    {
        key: 'excused',
        label: 'Excused',
        description: 'Approved excused absences',
        icon: HandHeart,
        value: summary.value.excused ?? 0,
        tone: 'text-primary bg-primary/5 border-primary/10',
    },
    {
        key: 'school-activity',
        label: 'School activity',
        description: 'Official school activity days',
        icon: Landmark,
        value: summary.value['school-activity'] ?? 0,
        tone: 'text-primary bg-primary/5 border-primary/10',
    },
    {
        key: 'absent',
        label: 'Absent',
        description: 'Submitted sessions marked absent',
        icon: CircleX,
        value: summary.value.absent ?? 0,
        tone: 'text-destructive bg-destructive/5 border-destructive/15',
    },
]);

const recordedSessions = computed(() => entries.value.reduce((total, entry) => total + entry.value, 0));

onMounted(async () => {
    try {
        summary.value = await portalApi.student.attendance();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <main class="w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12 lg:pt-16">
        <PortalPageHeader
            :icon="CalendarCheck2"
            eyebrow="Academics"
            index="02"
            title="Attendance"
            description="A private summary of attendance sessions submitted by your adviser or teacher."
        >
            <template #actions>
                <span
                    class="bg-primary/6 inline-flex items-center gap-2.5 rounded-full border border-primary/15 px-4 py-1.5 font-mono text-xs text-primary"
                >
                    {{ recordedSessions }} submitted sessions
                </span>
            </template>
        </PortalPageHeader>

        <div v-if="loading" class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="index in 5" :key="index" class="h-36 animate-pulse rounded-2xl bg-muted/60" />
        </div>

        <PortalEmptyState
            v-else-if="recordedSessions === 0"
            class="mt-12"
            :icon="CalendarCheck2"
            index="02"
            title="No submitted sessions yet"
            description="Your attendance summary will appear after an adviser or teacher submits attendance for your class."
        />

        <template v-else>
            <section class="portal-rise mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="(entry, index) in entries"
                    :key="entry.key"
                    class="relative overflow-hidden rounded-2xl border border-border/60 bg-card p-6"
                >
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <div class="flex items-center justify-between">
                        <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                        <span class="flex size-9 items-center justify-center rounded-xl border" :class="entry.tone">
                            <component :is="entry.icon" class="size-4" />
                        </span>
                    </div>
                    <p class="mt-6 text-[13px] font-medium text-muted-foreground">{{ entry.label }}</p>
                    <p class="mt-1 font-display text-4xl font-medium tracking-[-0.02em] text-foreground">{{ entry.value }}</p>
                    <p class="mt-3 text-xs leading-5 text-muted-foreground">{{ entry.description }}</p>
                </article>
            </section>

            <aside
                class="portal-rise mt-8 rounded-2xl border border-primary/15 bg-primary/5 p-5 text-sm leading-6 text-muted-foreground"
                style="animation-delay: 100ms"
            >
                <span class="font-medium text-foreground">Need a correction?</span>
                Contact your adviser or school registrar. Students can view submitted attendance but cannot change school records.
            </aside>
        </template>
    </main>
</template>
