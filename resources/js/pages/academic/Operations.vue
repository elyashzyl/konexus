<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import api, { extractError } from '@/lib/api';
import type { CrudItem, Paginated } from '@/types/crud';
import {
    ArrowRight,
    BadgeCheck,
    BookOpenCheck,
    CalendarCheck2,
    ChevronRight,
    ClipboardList,
    FileCheck2,
    GraduationCap,
    Layers3,
    Plus,
    RefreshCw,
    ShieldCheck,
    UsersRound,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { toast } from 'vue-sonner';

type CurriculumProgram = {
    id: number;
    name: string;
    code: string;
    framework: 'matatag' | 'k12-2016' | 'strengthened-shs' | 'local';
    calendar_type: 'quarterly' | 'semester';
    grade_level_ids: number[];
    compliance_status: 'deped-aligned' | 'local-adaptation';
    local_adaptation_reason: string | null;
    status: string;
    periods: Array<{ id: number; name: string; code: string; status: string }>;
};

type AcademicDashboard = {
    context: { academic_year: string | null; academic_term: string | null; campus_name: string | null };
    totals: {
        active_sections: number;
        subjects: number;
        subjects_without_teacher: number;
        teachers: number;
        enrolled_students: number;
        students_without_class: number;
        classes_today: number;
        upcoming_classes: number;
    };
};

const loading = ref(true);
const refreshing = ref(false);
const savingProgram = ref(false);
const dialogOpen = ref(false);
const errorMessage = ref<string | null>(null);
const dashboard = ref<AcademicDashboard | null>(null);
const programs = ref<CurriculumProgram[]>([]);
const academicYears = ref<CrudItem[]>([]);
const gradeLevels = ref<CrudItem[]>([]);

const programForm = reactive({
    academic_year_id: null as number | null,
    name: '',
    code: '',
    framework: 'matatag' as CurriculumProgram['framework'],
    calendar_type: 'quarterly' as CurriculumProgram['calendar_type'],
    grade_level_ids: [] as number[],
    compliance_status: 'deped-aligned' as CurriculumProgram['compliance_status'],
    local_adaptation_reason: '',
});

const contextLabel = computed(() => dashboard.value?.context.academic_year ?? 'Academic year not selected');
const activeProgramCount = computed(() => programs.value.filter((program) => program.status !== 'archived').length);
const adaptationCount = computed(() => programs.value.filter((program) => program.compliance_status === 'local-adaptation').length);

const metrics = computed(() => [
    {
        label: 'Official enrolments',
        value: dashboard.value?.totals.enrolled_students ?? 0,
        detail: 'Learners in the active academic context',
        icon: UsersRound,
        href: '/school/enrollments',
    },
    {
        label: 'Placement review',
        value: dashboard.value?.totals.students_without_class ?? 0,
        detail: 'Official enrolments without an active class',
        icon: GraduationCap,
        href: '/school/enrollments',
    },
    {
        label: 'Classes today',
        value: dashboard.value?.totals.classes_today ?? 0,
        detail: `${dashboard.value?.totals.upcoming_classes ?? 0} scheduled for the next operating day`,
        icon: CalendarCheck2,
        href: '/school/sections',
    },
    {
        label: 'Curriculum programs',
        value: activeProgramCount.value,
        detail: `${adaptationCount.value} local adaptation${adaptationCount.value === 1 ? '' : 's'} requiring review`,
        icon: Layers3,
        href: '#curriculum-programs',
    },
]);

const operations = computed<{ title: string; description: string; href: string; icon: LucideIcon; action: string }[]>(() => [
    {
        title: 'Curriculum versions',
        description: 'Manage school-year programs, period cadence, and DepEd alignment.',
        href: '#curriculum-programs',
        icon: Layers3,
        action: 'Review programs',
    },
    {
        title: 'Enrollment placement',
        description: 'Validate grade, section, program, and official learner placement.',
        href: '/school/enrollments',
        icon: GraduationCap,
        action: 'Open enrolments',
    },
    {
        title: 'Attendance readiness',
        description: 'Sections and advisers determine which daily sessions can be submitted.',
        href: '/school/sections',
        icon: CalendarCheck2,
        action: 'Open sections',
    },
    {
        title: 'Gradebook setup',
        description: 'Use subjects and offering data before teachers record assessments.',
        href: '/school/subjects',
        icon: BookOpenCheck,
        action: 'Open subjects',
    },
    {
        title: 'Promotion review',
        description: 'Finalized results feed authorised promotion decisions and report cards.',
        href: '/school/enrollments',
        icon: FileCheck2,
        action: 'Review learners',
    },
    {
        title: 'Internal records',
        description: 'Prepare roster and school-form data without claiming DepEd LIS issuance.',
        href: '/admin/reports',
        icon: ClipboardList,
        action: 'Open reports',
    },
]);

const actionQueue = computed(() => [
    {
        title: 'Learners needing a class',
        count: dashboard.value?.totals.students_without_class ?? 0,
        detail: 'Confirm section and class-roster placement.',
        href: '/school/enrollments',
        tone: 'bg-amber-50 text-amber-700 ring-amber-100',
    },
    {
        title: 'Subjects without a teacher',
        count: dashboard.value?.totals.subjects_without_teacher ?? 0,
        detail: 'Assign an instructor before assessment work begins.',
        href: '/school/subjects',
        tone: 'bg-primary/6 text-primary ring-primary/10',
    },
    {
        title: 'Local curriculum adaptations',
        count: adaptationCount.value,
        detail: 'Keep an approved reason with every local change.',
        href: '#curriculum-programs',
        tone: 'bg-primary/6 text-primary ring-primary/10',
    },
]);

function nameForGradeLevel(id: number): string {
    const level = gradeLevels.value.find((item) => item.id === id);

    return String(level?.name ?? `Grade level #${id}`);
}

function frameworkLabel(framework: CurriculumProgram['framework']): string {
    return {
        matatag: 'MATATAG',
        'k12-2016': 'Legacy K–12',
        'strengthened-shs': 'Strengthened SHS',
        local: 'School-managed',
    }[framework];
}

function resetProgramForm(): void {
    const currentYear = academicYears.value.find((year) => year.is_active);
    programForm.academic_year_id = currentYear?.id ?? academicYears.value[0]?.id ?? null;
    programForm.name = '';
    programForm.code = '';
    programForm.framework = 'matatag';
    programForm.calendar_type = 'quarterly';
    programForm.grade_level_ids = [];
    programForm.compliance_status = 'deped-aligned';
    programForm.local_adaptation_reason = '';
}

function openProgramDialog(): void {
    resetProgramForm();
    dialogOpen.value = true;
}

async function load(): Promise<void> {
    errorMessage.value = null;

    try {
        const [dashboardResponse, programsResponse, yearsResponse, gradeLevelsResponse] = await Promise.all([
            api.get<{ data: AcademicDashboard }>('/academic/dashboard'),
            api.get<{ data: CurriculumProgram[] }>('/academic-operations/programs'),
            api.get<{ data: Paginated<CrudItem> }>('/academic-years', { params: { per_page: 100, sort_by: 'start_date', sort_dir: 'desc' } }),
            api.get<{ data: Paginated<CrudItem> }>('/grade-levels', { params: { per_page: 100, sort_by: 'sequence', sort_dir: 'asc' } }),
        ]);

        dashboard.value = dashboardResponse.data.data;
        programs.value = programsResponse.data.data;
        academicYears.value = yearsResponse.data.data.items;
        gradeLevels.value = gradeLevelsResponse.data.data.items;
    } catch (error) {
        errorMessage.value = extractError(error);
    }
}

async function refresh(): Promise<void> {
    refreshing.value = true;
    await load();
    refreshing.value = false;
}

async function submitProgram(): Promise<void> {
    if (!programForm.academic_year_id || !programForm.name.trim() || !programForm.code.trim() || programForm.grade_level_ids.length === 0) {
        toast.error('Choose an academic year, name, code, and at least one grade level.');

        return;
    }

    if (programForm.compliance_status === 'local-adaptation' && !programForm.local_adaptation_reason.trim()) {
        toast.error('Describe why this curriculum is a local adaptation.');

        return;
    }

    savingProgram.value = true;

    try {
        await api.post('/academic-operations/programs', {
            ...programForm,
            local_adaptation_reason: programForm.local_adaptation_reason.trim() || null,
        });
        toast.success('Curriculum program created. Add its periods before use.');
        dialogOpen.value = false;
        await load();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        savingProgram.value = false;
    }
}

onMounted(async () => {
    await load();
    loading.value = false;
});
</script>

<template>
    <main class="relative min-h-full">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="ShieldCheck"
                eyebrow="Academics"
                title="Academic operations"
                :description="`A school-managed workspace for ${contextLabel}. Curriculum remains versioned, traceable, and visibly DepEd-aligned or locally adapted.`"
            >
                <template #actions>
                    <div class="flex items-center gap-3">
                        <Button variant="outline" size="sm" :disabled="refreshing" @click="refresh">
                            <RefreshCw class="size-3.5" :class="{ 'animate-spin': refreshing }" />
                            Refresh
                        </Button>
                        <Button size="sm" @click="openProgramDialog"><Plus class="size-3.5" /> New program</Button>
                    </div>
                </template>
            </AdminPageHeader>

            <div v-if="loading" class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="index in 4" :key="index" class="h-44 animate-pulse rounded-2xl bg-muted/60" />
            </div>

            <div v-else-if="errorMessage" class="mt-12 rounded-2xl border border-destructive/20 bg-destructive/5 p-6">
                <p class="font-medium text-foreground">Academic data could not be loaded.</p>
                <p class="mt-1 text-sm text-muted-foreground">{{ errorMessage }}</p>
                <Button class="mt-5" size="sm" @click="refresh"><RefreshCw class="size-3.5" /> Try again</Button>
            </div>

            <template v-else>
                <section class="portal-rise mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <RouterLink
                        v-for="(metric, index) in metrics"
                        :key="metric.label"
                        :to="metric.href"
                        class="group relative overflow-hidden rounded-2xl border border-border/60 bg-card p-6 transition-colors hover:border-primary/25"
                    >
                        <div
                            class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent"
                        />
                        <div class="flex items-center justify-between">
                            <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                            <component :is="metric.icon" class="size-4 text-muted-foreground/50 transition-colors group-hover:text-primary" />
                        </div>
                        <p class="mt-5 text-[13px] font-medium text-muted-foreground">{{ metric.label }}</p>
                        <p class="mt-1 font-display text-4xl font-medium tracking-[-0.02em] text-foreground">{{ metric.value }}</p>
                        <p class="mt-4 text-xs leading-5 text-muted-foreground">{{ metric.detail }}</p>
                    </RouterLink>
                </section>

                <section
                    id="curriculum-programs"
                    class="portal-rise mt-12 grid gap-6 lg:grid-cols-[minmax(0,1.75fr)_minmax(19rem,0.75fr)]"
                    style="animation-delay: 100ms"
                >
                    <article class="overflow-hidden rounded-2xl border border-border/60 bg-card">
                        <header class="flex flex-col gap-4 border-b border-border/60 p-6 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <Layers3 class="size-4 text-primary" />
                                    <p class="text-[11px] font-medium uppercase tracking-[0.2em] text-primary">Versioned catalog</p>
                                </div>
                                <h2 class="mt-3 font-display text-2xl font-medium tracking-[-0.015em] text-foreground">
                                    Curriculum & period coverage
                                </h2>
                                <p class="mt-1.5 text-sm text-muted-foreground">
                                    Programs are frozen per school year; earlier learner records remain historically intact.
                                </p>
                            </div>
                            <Button size="sm" @click="openProgramDialog"><Plus class="size-3.5" /> New program</Button>
                        </header>

                        <div v-if="programs.length" class="divide-y divide-border/60">
                            <article
                                v-for="program in programs"
                                :key="program.id"
                                class="grid gap-5 p-6 md:grid-cols-[minmax(0,1fr)_auto] md:items-center"
                            >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-medium text-foreground">{{ program.name }}</p>
                                        <span
                                            class="rounded-md border border-border/80 bg-muted/50 px-2 py-0.5 font-mono text-[10px] uppercase tracking-[0.08em] text-muted-foreground"
                                            >{{ program.code }}</span
                                        >
                                    </div>
                                    <p class="mt-1.5 text-sm text-muted-foreground">
                                        {{ frameworkLabel(program.framework) }} ·
                                        {{ program.calendar_type === 'quarterly' ? 'Quarterly' : 'Semester' }} ·
                                        {{ program.grade_level_ids.map(nameForGradeLevel).join(', ') }}
                                    </p>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1"
                                            :class="
                                                program.compliance_status === 'deped-aligned'
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                    : 'border-amber-200 bg-amber-50 text-amber-700'
                                            "
                                        >
                                            <BadgeCheck class="size-3.5" />
                                            {{ program.compliance_status === 'deped-aligned' ? 'DepEd-aligned' : 'Local adaptation' }}
                                        </span>
                                        <span class="rounded-full border border-border/70 px-2.5 py-1 text-muted-foreground"
                                            >{{ program.periods.length }} configured periods</span
                                        >
                                    </div>
                                    <p v-if="program.local_adaptation_reason" class="mt-3 text-xs leading-5 text-muted-foreground">
                                        Reason: {{ program.local_adaptation_reason }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 md:justify-end">
                                    <span
                                        class="rounded-lg bg-muted px-2.5 py-1 font-mono text-[10px] uppercase tracking-[0.12em] text-muted-foreground"
                                        >{{ program.status }}</span
                                    >
                                </div>
                            </article>
                        </div>

                        <div v-else class="p-10 text-center">
                            <Layers3 class="mx-auto size-7 text-primary" />
                            <p class="mt-4 font-display text-xl font-medium text-foreground">No academic programs yet</p>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                                Create the curriculum version that applies to the selected school year before official enrollment is placed.
                            </p>
                            <Button class="mt-5" size="sm" @click="openProgramDialog"><Plus class="size-3.5" /> Create first program</Button>
                        </div>
                    </article>

                    <aside class="overflow-hidden rounded-2xl border border-border/60 bg-card">
                        <header class="border-b border-border/60 p-6">
                            <p class="text-[11px] font-medium uppercase tracking-[0.2em] text-primary">Office attention</p>
                            <h2 class="mt-3 font-display text-2xl font-medium tracking-[-0.015em] text-foreground">Action queue</h2>
                        </header>
                        <div class="p-2">
                            <RouterLink
                                v-for="item in actionQueue"
                                :key="item.title"
                                :to="item.href"
                                class="group flex items-center gap-4 rounded-xl p-4 hover:bg-muted/50"
                            >
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl ring-1" :class="item.tone"
                                    ><span class="font-display text-lg font-medium">{{ item.count }}</span></span
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-foreground">{{ item.title }}</span>
                                    <span class="mt-0.5 block text-xs leading-5 text-muted-foreground">{{ item.detail }}</span>
                                </span>
                                <ChevronRight
                                    class="size-4 shrink-0 text-muted-foreground/50 transition group-hover:translate-x-0.5 group-hover:text-primary"
                                />
                            </RouterLink>
                        </div>
                        <div class="border-t border-border/60 bg-muted/30 px-6 py-4 text-xs leading-5 text-muted-foreground">
                            Academic data is school-managed. Local changes should be approved by the principal or registrar.
                        </div>
                    </aside>
                </section>

                <section class="portal-rise mt-12" style="animation-delay: 180ms">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                        <div>
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Workflow launchpad</p>
                            <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">Academic operations</h2>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Each workspace keeps responsibilities separated between academic office, advisers, teachers, and released learner
                                records.
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-2 text-xs text-muted-foreground"
                            ><ShieldCheck class="size-3.5 text-primary" /> Role-controlled data access</span
                        >
                    </div>

                    <div class="mt-7 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <RouterLink
                            v-for="operation in operations"
                            :key="operation.title"
                            :to="operation.href"
                            class="group rounded-2xl border border-border/60 bg-card p-6 transition-colors hover:border-primary/25"
                        >
                            <span class="bg-primary/7 flex size-10 items-center justify-center rounded-xl text-primary ring-1 ring-primary/10"
                                ><component :is="operation.icon" class="size-4"
                            /></span>
                            <h3 class="mt-5 font-display text-xl font-medium tracking-[-0.01em] text-foreground">{{ operation.title }}</h3>
                            <p class="mt-2 min-h-12 text-sm leading-6 text-muted-foreground">{{ operation.description }}</p>
                            <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-medium text-primary"
                                >{{ operation.action }} <ArrowRight class="size-3.5 transition group-hover:translate-x-0.5"
                            /></span>
                        </RouterLink>
                    </div>
                </section>

                <footer class="portal-rise mt-16 border-t border-border/60 pt-6 text-xs text-muted-foreground" style="animation-delay: 240ms">
                    System-generated academic outputs support internal and LIS-ready preparation only; they are not official DepEd LIS forms.
                </footer>
            </template>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="font-display text-2xl font-medium">New curriculum program</DialogTitle>
                    <DialogDescription
                        >Attach this version to a school year and grade range. Historical enrollments will retain their snapshots.</DialogDescription
                    >
                </DialogHeader>

                <form class="grid gap-5 py-2" @submit.prevent="submitProgram">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="program-year">Academic year</Label>
                            <select
                                id="program-year"
                                v-model="programForm.academic_year_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                required
                            >
                                <option :value="null" disabled>Select an academic year</option>
                                <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="program-code">Code</Label>
                            <Input id="program-code" v-model="programForm.code" placeholder="e.g. MATATAG-1-9" required />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="program-name">Program name</Label>
                        <Input id="program-name" v-model="programForm.name" placeholder="e.g. MATATAG Curriculum, Grades 1–9" required />
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="program-framework">Framework</Label>
                            <select
                                id="program-framework"
                                v-model="programForm.framework"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option value="matatag">MATATAG</option>
                                <option value="k12-2016">Legacy K–12</option>
                                <option value="strengthened-shs">Strengthened SHS</option>
                                <option value="local">School-managed</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="program-cadence">Period cadence</Label>
                            <select
                                id="program-cadence"
                                v-model="programForm.calendar_type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option value="quarterly">Quarterly</option>
                                <option value="semester">Semester</option>
                            </select>
                        </div>
                    </div>
                    <fieldset class="grid gap-3">
                        <legend class="text-sm font-medium text-foreground">Applicable grade levels</legend>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            <label
                                v-for="level in gradeLevels"
                                :key="level.id"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/70 px-3 py-2 text-sm transition hover:border-primary/25"
                            >
                                <input
                                    v-model="programForm.grade_level_ids"
                                    type="checkbox"
                                    :value="level.id"
                                    class="size-4 rounded border-input text-primary focus:ring-ring"
                                />
                                {{ level.name }}
                            </label>
                        </div>
                    </fieldset>
                    <fieldset class="grid gap-3">
                        <legend class="text-sm font-medium text-foreground">Compliance status</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label class="flex cursor-pointer gap-3 rounded-lg border border-border/70 p-3 text-sm">
                                <input
                                    v-model="programForm.compliance_status"
                                    value="deped-aligned"
                                    type="radio"
                                    class="mt-0.5 text-primary focus:ring-ring"
                                />
                                <span
                                    ><span class="block font-medium">DepEd-aligned</span
                                    ><span class="mt-0.5 block text-xs leading-5 text-muted-foreground"
                                        >Uses the selected DepEd template without local changes.</span
                                    ></span
                                >
                            </label>
                            <label class="flex cursor-pointer gap-3 rounded-lg border border-border/70 p-3 text-sm">
                                <input
                                    v-model="programForm.compliance_status"
                                    value="local-adaptation"
                                    type="radio"
                                    class="mt-0.5 text-primary focus:ring-ring"
                                />
                                <span
                                    ><span class="block font-medium">Local adaptation</span
                                    ><span class="mt-0.5 block text-xs leading-5 text-muted-foreground"
                                        >Requires an auditable school-approved reason.</span
                                    ></span
                                >
                            </label>
                        </div>
                    </fieldset>
                    <div v-if="programForm.compliance_status === 'local-adaptation'" class="grid gap-2">
                        <Label for="adaptation-reason">Adaptation reason</Label>
                        <textarea
                            id="adaptation-reason"
                            v-model="programForm.local_adaptation_reason"
                            rows="3"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Describe the approved local change."
                        />
                    </div>
                    <DialogFooter class="pt-2">
                        <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="savingProgram"
                            ><RefreshCw v-if="savingProgram" class="size-3.5 animate-spin" /> Create program</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </main>
</template>
