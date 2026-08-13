<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import PortalPageHeader from '@/components/portal/PortalPageHeader.vue';
import { extractError } from '@/lib/api';
import { portalApi, type AdvisoryStudent } from '@/lib/portalApi';
import { Users } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const advisory = ref<{ id: number; name: string; section: string | null; students: AdvisoryStudent[] } | null>(null);

onMounted(async () => {
    try {
        advisory.value = await portalApi.teacher.advisoryClass();
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
        <PortalPageHeader
            :icon="Users"
            eyebrow="Classroom"
            index="03"
            title="Advisory class"
            description="Your advisory class and the students assigned to you."
        >
            <template #actions>
                <span v-if="advisory" class="inline-flex items-center gap-2.5 rounded-full border border-primary/15 bg-primary/6 px-4 py-1.5 font-mono text-xs text-primary">
                    <span class="index-num">{{ advisory.students.length }}</span> students
                </span>
            </template>
        </PortalPageHeader>

        <div v-if="loading" class="mt-12 space-y-3">
            <div v-for="i in 4" :key="i" class="h-12 animate-pulse rounded-xl bg-muted/60" />
        </div>

        <div v-else-if="!advisory" class="mt-12">
            <PortalEmptyState
                :icon="Users"
                index="03"
                title="No advisory class"
                description="You are not currently assigned as an adviser."
            />
        </div>

        <div v-else class="portal-rise mt-12 rounded-2xl border border-border/60 bg-card px-6 py-6 sm:px-8">
            <div>
                <p class="font-display text-2xl font-medium tracking-[-0.01em] text-foreground">
                    {{ advisory.name }} <span v-if="advisory.section" class="text-muted-foreground">· {{ advisory.section }}</span>
                </p>
            </div>

            <div class="editorial-rule mt-5 h-px" />

            <div v-if="advisory.students.length" class="mt-3 divide-y divide-border/60">
                <div v-for="(student, index) in advisory.students" :key="student.id" class="flex items-center gap-4 py-3">
                    <span class="index-num w-7 shrink-0 font-mono text-xs text-muted-foreground/60">{{ pad(index) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[15px] font-medium text-foreground">{{ student.name }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ student.lrn }}</p>
                    </div>
                    <span class="shrink-0 font-mono text-xs text-muted-foreground">{{ student.gender }}</span>
                </div>
            </div>
            <p v-else class="py-6 text-center text-sm text-muted-foreground">No students in the advisory class.</p>
        </div>
    </main>
</template>
