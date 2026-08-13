<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { extractError } from '@/lib/api';
import { portalApi, type TeacherAssignmentItem, type TeacherRoster } from '@/lib/portalApi';
import { ClipboardList } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const assignments = ref<TeacherAssignmentItem[]>([]);
const roster = ref<TeacherRoster | null>(null);
const rosterLoading = ref(false);
const selectedAssignment = ref<TeacherAssignmentItem | null>(null);

onMounted(async () => {
    try {
        assignments.value = await portalApi.teacher.assignments();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

async function openRoster(assignment: TeacherAssignmentItem): Promise<void> {
    selectedAssignment.value = assignment;
    rosterLoading.value = true;
    try {
        roster.value = await portalApi.teacher.roster(assignment.section_id);
    } catch (error) {
        toast.error(extractError(error));
        roster.value = null;
    } finally {
        rosterLoading.value = false;
    }
}

function pad(index: number): string {
    return String(index + 1).padStart(2, '0');
}
</script>

<template>
    <main class="w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12 lg:pt-16">
        <PortalPageHeader
            :icon="ClipboardList"
            eyebrow="Teaching load"
            index="01"
            title="My classes"
            description="Teaching assignments for the current term, with class rosters."
        >
            <template #actions>
                <span v-if="assignments.length" class="inline-flex items-center gap-2.5 rounded-full border border-primary/15 bg-primary/6 px-4 py-1.5 font-mono text-xs text-primary">
                    <span class="index-num">{{ assignments.length }}</span> classes
                </span>
            </template>
        </PortalPageHeader>

        <div v-if="loading" class="mt-12 space-y-3">
            <div v-for="i in 4" :key="i" class="h-12 animate-pulse rounded-xl bg-muted/60" />
        </div>

        <div v-else-if="!assignments.length" class="mt-12">
            <PortalEmptyState
                :icon="ClipboardList"
                index="01"
                title="No teaching assignments"
                description="Class assignments will appear here once the registrar publishes them."
            />
        </div>

        <template v-else>
            <div class="portal-rise mt-12 divide-y divide-border/60 border-y border-border/60">
                <button
                    v-for="(assignment, index) in assignments"
                    :key="assignment.id"
                    type="button"
                    class="group flex w-full items-center gap-4 py-4 text-left"
                    :class="selectedAssignment?.id === assignment.id ? 'bg-primary/4' : ''"
                    @click="openRoster(assignment)"
                >
                    <span class="index-num w-7 shrink-0 font-mono text-xs text-muted-foreground/60 transition group-hover:text-primary">
                        {{ pad(index) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[15px] font-medium text-foreground">{{ assignment.subject }} · {{ assignment.section }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ assignment.term }} · {{ assignment.campus }}</p>
                    </div>
                    <span class="shrink-0 rounded-full border border-primary/15 bg-primary/6 px-3 py-1 font-mono text-[11px] text-primary">
                        View roster
                    </span>
                </button>
            </div>

            <div v-if="rosterLoading" class="mt-8 space-y-3">
                <div v-for="i in 4" :key="i" class="h-10 animate-pulse rounded-xl bg-muted/60" />
            </div>

            <div v-else-if="roster" class="portal-rise mt-8 rounded-2xl border border-border/60 bg-card px-6 py-6 sm:px-8">
                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div>
                        <p class="font-display text-2xl font-medium tracking-[-0.01em] text-foreground">{{ roster.subject }}</p>
                        <p class="mt-0.5 text-sm text-muted-foreground">Class roster</p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2.5 rounded-full border border-primary/15 bg-primary/6 px-4 py-1.5 font-mono text-xs text-primary">
                        <span class="index-num">{{ roster.items.length }}</span> students
                    </span>
                </div>

                <div class="editorial-rule mt-5 h-px" />

                <div v-if="roster.items.length" class="mt-4 divide-y divide-border/60">
                    <div v-for="(student, index) in roster.items" :key="student.student_id" class="flex items-center gap-4 py-3">
                        <span class="index-num w-7 shrink-0 font-mono text-xs text-muted-foreground/60">{{ pad(index) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[15px] font-medium text-foreground">{{ student.name }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ student.lrn }}</p>
                        </div>
                        <span class="shrink-0 font-mono text-xs text-muted-foreground">{{ student.gender }}</span>
                        <span class="shrink-0 font-mono text-sm font-medium text-foreground">{{ student.final_grade ?? '—' }}</span>
                    </div>
                </div>
                <p v-else class="py-6 text-center text-sm text-muted-foreground">No students in this roster.</p>
            </div>
        </template>
    </main>
</template>
