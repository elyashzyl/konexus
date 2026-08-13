<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { extractError } from '@/lib/api';
import { portalApi } from '@/lib/portalApi';
import type { AcademicSummary } from '@/types/platform';
import { ArrowLeft, BookOpen } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { toast } from 'vue-sonner';

const route = useRoute();
const childId = computed(() => Number(route.params.id));

const loading = ref(true);
const grades = ref<AcademicSummary | null>(null);

onMounted(async () => {
    try {
        grades.value = await portalApi.parent.grades(childId.value);
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

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
                :icon="BookOpen"
                eyebrow="Academics"
                index="01"
                title="Grades and report cards"
                description="Published grading records from the current school year."
            >
                <template #actions>
                    <span v-if="grades?.records.length" class="inline-flex items-center gap-2.5 rounded-full border border-primary/15 bg-primary/6 px-4 py-1.5 font-mono text-xs text-primary">
                        <span class="index-num">{{ grades.records.length }}</span> records
                    </span>
                </template>
            </PortalPageHeader>
        </div>

        <div v-if="loading" class="mt-12 space-y-3">
            <div v-for="i in 4" :key="i" class="h-12 animate-pulse rounded-xl bg-muted/60" />
        </div>

        <div v-else-if="!grades?.records.length" class="mt-12">
            <PortalEmptyState
                :icon="BookOpen"
                index="01"
                title="Grades are private"
                description="Published grading records will appear here as soon as they are released by the registrar."
            />
        </div>

        <div v-else class="portal-rise mt-12 divide-y divide-border/60 border-y border-border/60">
            <div v-for="(record, index) in grades.records" :key="record.id" class="flex items-center gap-4 py-4">
                <span class="index-num w-7 shrink-0 font-mono text-xs text-muted-foreground/60">{{ pad(index) }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[15px] font-medium text-foreground">{{ record.subject }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ record.term }} · {{ record.subject_code }}</p>
                </div>
                <span class="shrink-0 rounded-full border border-primary/15 bg-primary/6 px-3 py-1 font-mono text-[11px] text-primary">{{ record.status }}</span>
                <span class="shrink-0 font-mono text-sm font-medium text-foreground">{{ record.final_grade ?? '—' }}</span>
            </div>
        </div>

        <footer v-if="grades?.records.length" class="mt-8 flex flex-col justify-between gap-3 text-xs text-muted-foreground sm:flex-row sm:items-center">
            <p>General average <span class="font-mono font-medium text-foreground">{{ grades.general_average ?? '—' }}</span></p>
            <p>{{ grades.published_records }} published records</p>
        </footer>
    </main>
</template>
