<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { extractError } from '@/lib/api';
import { portalApi } from '@/lib/portalApi';
import type { PortalEnrollment } from '@/types/platform';
import { CalendarDays, CheckCircle2, GraduationCap, MapPin } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const enrollments = ref<PortalEnrollment[]>([]);

onMounted(async () => {
    try {
        enrollments.value = await portalApi.student.enrollments();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

function formatDate(value?: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}

function pad(index: number): string {
    return String(index + 1).padStart(2, '0');
}
</script>

<template>
    <main class="w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12 lg:pt-16">
        <PortalPageHeader
            :icon="GraduationCap"
            eyebrow="Registrar record"
            index="03"
            title="Enrollment history"
            description="Every school year on the official record, kept by the registrar."
        />

        <div v-if="loading" class="mt-12 space-y-4">
            <div v-for="i in 3" :key="i" class="h-28 animate-pulse rounded-2xl bg-muted/60" />
        </div>

        <div v-else-if="!enrollments.length" class="mt-12">
            <PortalEmptyState
                :icon="GraduationCap"
                index="03"
                title="No enrollment history yet"
                description="Enrollment records will appear here once they have been published."
            />
        </div>

        <div v-else class="portal-rise relative mt-12">
            <div class="absolute top-3 bottom-3 left-[1.15rem] w-px bg-border sm:left-[1.35rem]" />
            <div class="space-y-5">
                <article v-for="(enrollment, index) in enrollments" :key="enrollment.id" class="relative flex gap-5 pl-0 sm:gap-6">
                    <div class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full border border-border/70 bg-card font-mono text-[11px] text-muted-foreground ring-4 ring-background sm:size-11">
                        <span class="index-num">{{ pad(index) }}</span>
                    </div>
                    <div class="min-w-0 flex-1 rounded-2xl border border-border/60 bg-card px-6 py-6 sm:px-8">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                            <div>
                                <p class="font-display text-2xl font-medium tracking-[-0.01em] text-foreground">
                                    {{ enrollment.grade_level }} {{ enrollment.section }}
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">{{ enrollment.academic_year }}</p>
                            </div>
                            <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-primary/15 bg-primary/6 px-3 py-1 text-[11px] font-medium text-primary">
                                <CheckCircle2 class="size-3.5" />
                                {{ enrollment.status_label }}
                            </span>
                        </div>
                        <div class="editorial-rule mt-6 h-px" />
                        <div class="mt-5 grid gap-3 text-sm sm:grid-cols-2">
                            <div class="flex items-start gap-2.5">
                                <MapPin class="mt-0.5 size-4 shrink-0 text-primary" />
                                <span class="text-muted-foreground">{{ enrollment.campus }}</span>
                            </div>
                            <div class="flex items-start gap-2.5">
                                <CalendarDays class="mt-0.5 size-4 shrink-0 text-primary" />
                                <span class="text-muted-foreground">Enrolled {{ formatDate(enrollment.date_enrolled) }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </main>
</template>
