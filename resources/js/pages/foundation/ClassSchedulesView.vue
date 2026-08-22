<script setup lang="ts">
import FormDialog from '@/components/crud/FormDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import type { CrudField } from '@/types/crud';
import { CalendarClock, CalendarPlus, Pencil, RefreshCw, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface Option {
    value: number;
    label: string;
}

interface SectionRecord extends Record<string, unknown> {
    id: number;
    name: string;
    grade_level_id?: number | null;
}

interface TimetableEntry {
    id: number;
    day: string;
    start_time: string | null;
    end_time: string | null;
    subject: { name: string; code?: string } | null;
    teacher: { name: string | null | undefined } | null;
    room: { name: string } | null;
}

interface ListPayload<T> {
    items: T[];
}

const DAYS = [
    { key: 'monday', label: 'Monday', short: 'Mon' },
    { key: 'tuesday', label: 'Tuesday', short: 'Tue' },
    { key: 'wednesday', label: 'Wednesday', short: 'Wed' },
    { key: 'thursday', label: 'Thursday', short: 'Thu' },
    { key: 'friday', label: 'Friday', short: 'Fri' },
    { key: 'saturday', label: 'Saturday', short: 'Sat' },
    { key: 'sunday', label: 'Sunday', short: 'Sun' },
];

const DAY_OPTIONS = DAYS.map((day) => ({ value: day.key, label: day.label }));

const gradeLevels = ref<Option[]>([]);
const sections = ref<SectionRecord[]>([]);
const academicYears = ref<(Option & { is_active?: boolean })[]>([]);
const subjects = ref<Option[]>([]);
const teachers = ref<Option[]>([]);
const rooms = ref<Option[]>([]);

const selectedYearId = ref<string>('');
const selectedGradeLevelId = ref<string>('all');
const selectedSectionId = ref<string>('');

const timetable = ref<Record<string, TimetableEntry[]>>({});
const loadingGrid = ref(false);
const gridError = ref('');

const dialogOpen = ref(false);
const editingEntry = ref<TimetableEntry | null>(null);
const submitting = ref(false);
const formError = ref('');
const fieldErrors = ref<Record<string, string[]>>({});

const pickersLoaded = ref(false);

const sectionOptions = computed(() =>
    sections.value
        .filter(
            (section) =>
                selectedGradeLevelId.value === 'all' ||
                Number(section.grade_level_id) === Number(selectedGradeLevelId.value),
        )
        .map((section) => ({ value: section.id, label: section.name })),
);

const gradeNameById = computed<Record<number, string>>(() => {
    const map: Record<number, string> = {};
    for (const level of gradeLevels.value) {
        map[Number(level.value)] = level.label;
    }
    return map;
});

function sectionById(id: string | number | undefined): SectionRecord | undefined {
    if (id === '' || id === undefined || id === null) {
        return undefined;
    }

    return sections.value.find((candidate) => candidate.id === Number(id));
}

const dialogOptions = computed(() => ({
    academic_year_id: academicYears.value,
    grade_level_id: gradeLevels.value,
    section_id: sections.value.map((section) => ({
        value: section.id,
        label:
            section.grade_level_id && gradeNameById.value[Number(section.grade_level_id)]
                ? `${gradeNameById.value[Number(section.grade_level_id)]} · ${section.name}`
                : section.name,
    })),
    subject_id: subjects.value,
    teacher_id: teachers.value,
    room_id: rooms.value,
    day: DAY_OPTIONS,
}));

const dialogFields: CrudField[] = [
    { name: 'academic_year_id', label: 'Academic year', type: 'select', required: true },
    { name: 'grade_level_id', label: 'Grade level', type: 'select', required: true },
    { name: 'section_id', label: 'Section', type: 'select', required: true },
    { name: 'subject_id', label: 'Subject', type: 'select', required: true },
    { name: 'teacher_id', label: 'Teacher', type: 'select', required: true },
    { name: 'room_id', label: 'Room', type: 'select' },
    {
        name: 'day',
        label: 'Day',
        type: 'select',
        required: true,
        options: DAY_OPTIONS,
    },
    { name: 'start_time', label: 'Start time', type: 'time', required: true, placeholder: '08:00' },
    { name: 'end_time', label: 'End time', type: 'time', required: true, placeholder: '09:00' },
];

const dialogInitialValues = computed<Record<string, any> | null>(() => {
    if (!dialogOpen.value || editingEntry.value) {
        return null;
    }

    const section = sectionById(selectedSectionId.value);

    return {
        academic_year_id: selectedYearId.value,
        grade_level_id: section?.grade_level_id
            ? String(section.grade_level_id)
            : selectedGradeLevelId.value !== 'all'
              ? selectedGradeLevelId.value
              : '',
        section_id: selectedSectionId.value,
    };
});

let dialogInitial: Record<string, any> | null = null;

const effectiveInitialValues = computed<Record<string, any> | null>(() => {
    if (editingEntry.value) {
        return dialogInitial;
    }

    return dialogInitialValues.value;
});

const weekly = computed<Record<string, TimetableEntry[]>>(() => {
    const grouped: Record<string, TimetableEntry[]> = {};
    for (const day of DAYS) {
        grouped[day.key] = [...(timetable.value[day.key] ?? [])].sort((a, b) =>
            (a.start_time ?? '').localeCompare(b.start_time ?? ''),
        );
    }
    return grouped;
});

const busiest = computed(() => Math.max(1, ...DAYS.map((day) => weekly.value[day.key]?.length ?? 0)));

const selectedSectionRecord = computed<SectionRecord | undefined>(() => sectionById(selectedSectionId.value));

function formatTime(value: string | null): string {
    if (!value) return '';
    const [hours, minutes] = value.split(':');
    const hour = Number(hours);
    if (Number.isNaN(hour)) return value;
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const display = hour % 12 === 0 ? 12 : hour % 12;
    return `${display}:${minutes ?? '00'} ${suffix}`;
}

function mapOptions(items: Array<Record<string, any>>, labelKeys: string[]): Option[] {
    return items.map((item) => ({
        value: item.id,
        label: labelKeys.map((key) => item[key]).find((label) => typeof label === 'string' && label.trim() !== '') ?? `#${item.id}`,
    }));
}

async function loadContext(): Promise<void> {
    const [gradeRes, sectionRes, yearRes] = await Promise.all([
        api.get<{ data: ListPayload<Record<string, any>> }>('/grade-levels', {
            params: { per_page: 100, sort_by: 'id', sort_dir: 'asc' },
        }),
        api.get<{ data: ListPayload<SectionRecord> }>('/sections', {
            params: { per_page: 100, sort_by: 'id', sort_dir: 'asc' },
        }),
        api.get<{ data: ListPayload<Record<string, any>> }>('/academic-years', {
            params: { per_page: 100, sort_by: 'id', sort_dir: 'asc' },
        }),
    ]);

    gradeLevels.value = mapOptions(gradeRes.data.data.items, ['name']);
    sections.value = sectionRes.data.data.items;

    academicYears.value = yearRes.data.data.items.map((item) => ({
        value: item.id,
        label: String(item.name ?? `#${item.id}`),
        is_active: Boolean(item.is_active),
    }));

    const active = academicYears.value.find((year) => year.is_active);
    selectedYearId.value = active ? String(active.value) : academicYears.value[0] ? String(academicYears.value[0].value) : '';

    if (selectedSectionId.value === '') {
        selectedSectionId.value = sectionOptions.value[0] ? String(sectionOptions.value[0].value) : '';
    }
}

async function loadPickers(): Promise<void> {
    if (pickersLoaded.value) return;

    const [subjectRes, teacherRes, roomRes] = await Promise.all([
        api.get<{ data: ListPayload<Record<string, any>> }>('/subjects', {
            params: { per_page: 100, sort_by: 'id', sort_dir: 'asc' },
        }),
        api.get<{ data: ListPayload<Record<string, any>> }>('/teachers', {
            params: { per_page: 100, sort_by: 'id', sort_dir: 'asc' },
        }),
        api.get<{ data: ListPayload<Record<string, any>> }>('/rooms', {
            params: { per_page: 100, sort_by: 'id', sort_dir: 'asc' },
        }),
    ]);

    subjects.value = mapOptions(subjectRes.data.data.items, ['name']);
    rooms.value = mapOptions(roomRes.data.data.items, ['name', 'code']);

    teachers.value = teacherRes.data.data.items.map((teacher) => ({
        value: teacher.id,
        label: teacher.employee?.name ?? `Teacher #${teacher.id}`,
    }));

    pickersLoaded.value = true;
}

async function loadTimetable(): Promise<void> {
    if (!selectedSectionId.value) {
        timetable.value = {};
        return;
    }

    loadingGrid.value = true;
    gridError.value = '';

    try {
        const response = await api.get<{ data: { days?: string[]; grid?: Record<string, TimetableEntry[]> } }>(
            `/schedules/sections/${selectedSectionId.value}/timetable`,
            {
                params: selectedYearId.value
                    ? { 'filter[academic_year_id]': selectedYearId.value }
                    : undefined,
            },
        );

        timetable.value = response.data.data?.grid ?? {};
    } catch (error) {
        gridError.value = extractError(error);
        timetable.value = {};
    } finally {
        loadingGrid.value = false;
    }
}

watch(selectedSectionId, () => {
    void loadTimetable();
});

watch(selectedGradeLevelId, () => {
    const stillVisible = sectionOptions.value.some((option) => String(option.value) === selectedSectionId.value);

    if (!stillVisible) {
        selectedSectionId.value = sectionOptions.value[0] ? String(sectionOptions.value[0].value) : '';
    }
});

watch(selectedYearId, () => {
    if (selectedSectionId.value !== '') {
        void loadTimetable();
    }
});

function openCreate(): void {
    editingEntry.value = null;
    formError.value = '';
    fieldErrors.value = {};

    loadPickers()
        .then(() => {
            dialogOpen.value = true;
        })
        .catch((error) => {
            formError.value = extractError(error);
        });
}

async function openEdit(entry: TimetableEntry): Promise<void> {
    await loadPickers();

    try {
        const response = await api.get<{ data: Record<string, any> }>(`/schedules/${entry.id}`);
        const record = response.data.data;

        dialogInitial = {
            id: record.id,
            academic_year_id: record.academic_year_id ? String(record.academic_year_id) : '',
            grade_level_id:
                record.grade_level_id
                    ? String(record.grade_level_id)
                    : sectionById(record.section_id)?.grade_level_id
                      ? String(sectionById(record.section_id)!.grade_level_id)
                      : '',
            section_id: record.section_id ? String(record.section_id) : '',
            subject_id: record.subject_id ? String(record.subject_id) : '',
            teacher_id: record.teacher_id ? String(record.teacher_id) : '',
            room_id: record.room_id ? String(record.room_id) : '',
            day: record.day ?? '',
            start_time: record.start_time ?? '',
            end_time: record.end_time ?? '',
        };
        editingEntry.value = entry;
        formError.value = '';
        fieldErrors.value = {};
        dialogOpen.value = true;
    } catch (error) {
        formError.value = extractError(error);
    }
}

async function handleSave(payload: Record<string, unknown>): Promise<void> {
    submitting.value = true;
    formError.value = '';
    fieldErrors.value = {};

    const sectionId = Number(payload.section_id ?? selectedSectionId.value);
    const yearId = Number(payload.academic_year_id ?? selectedYearId.value);
    const subjectId = Number(payload.subject_id);
    const teacherId = Number(payload.teacher_id);
    const roomId = payload.room_id ? Number(payload.room_id) : null;
    const section = sectionById(sectionId);

    // The section determines the grade; fall back to the picked grade only
    // when the section's grade is unknown.
    const derivedGrade = section?.grade_level_id ?? (Number.isNaN(Number(payload.grade_level_id)) ? undefined : Number(payload.grade_level_id));

    const body: Record<string, unknown> = {
        ...payload,
        academic_year_id: Number.isNaN(yearId) ? undefined : yearId,
        section_id: sectionId,
        subject_id: Number.isNaN(subjectId) ? undefined : subjectId,
        teacher_id: Number.isNaN(teacherId) ? undefined : teacherId,
        room_id: roomId,
        grade_level_id: derivedGrade,
    };

    try {
        if (editingEntry.value) {
            await api.put(`/schedules/${editingEntry.value.id}`, body);
        } else {
            await api.post('/schedules', body);
        }

        handleDialogClose(false);
        await loadTimetable();
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        formError.value = extractError(error);
    } finally {
        submitting.value = false;
    }
}

async function handleDelete(entry: TimetableEntry): Promise<void> {
    const confirmed = window.confirm('Remove this schedule entry?');

    if (!confirmed) {
        return;
    }

    try {
        await api.delete(`/schedules/${entry.id}`);
        await loadTimetable();
    } catch (error) {
        gridError.value = extractError(error);
    }
}

function handleDialogClose(value: boolean): void {
    dialogOpen.value = value;

    if (!value) {
        dialogInitial = null;
        editingEntry.value = null;
    }
}

onMounted(async () => {
    try {
        await loadContext();
        await loadTimetable();
    } catch (error) {
        gridError.value = extractError(error);
    }
});
</script>

<template>
    <div class="flex flex-col gap-6 px-4 py-6 lg:px-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight">Class schedules</h1>
                <p class="text-sm text-muted-foreground">Build the weekly Monday–Sunday timetable for each section.</p>
            </div>

            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" :disabled="loadingGrid" @click="loadTimetable()">
                    <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': loadingGrid }" />
                    Refresh
                </Button>
                <Button size="sm" @click="openCreate">
                    <CalendarPlus class="mr-2 h-4 w-4" />
                    Add schedule
                </Button>
            </div>
        </div>

        <Card>
            <CardContent class="grid gap-3 pt-6 sm:grid-cols-3">
                <div class="space-y-1.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Academic year</p>
                    <Select v-model="selectedYearId">
                        <SelectTrigger>
                            <SelectValue placeholder="All years" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="year in academicYears" :key="year.value" :value="String(year.value)">
                                {{ year.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Grade level</p>
                    <Select v-model="selectedGradeLevelId">
                        <SelectTrigger>
                            <SelectValue placeholder="All grade levels" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All grade levels</SelectItem>
                            <SelectItem v-for="level in gradeLevels" :key="level.value" :value="String(level.value)">
                                {{ level.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Section</p>
                    <Select v-model="selectedSectionId">
                        <SelectTrigger>
                            <SelectValue placeholder="Choose a section…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in sectionOptions" :key="option.value" :value="String(option.value)">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>

        <p v-if="gridError" class="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
            {{ gridError }}
        </p>

        <Card v-if="!selectedSectionId">
            <CardContent class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <CalendarClock class="h-10 w-10 text-muted-foreground/40" />
                <div>
                    <CardTitle class="text-base">No section selected</CardTitle>
                    <CardDescription>Pick an academic year and section above to view its weekly timetable.</CardDescription>
                </div>
            </CardContent>
        </Card>

        <Card v-else>
            <CardHeader class="pb-3">
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle class="text-base">Weekly timetable</CardTitle>
                        <CardDescription>
                            <template v-if="selectedSectionRecord">
                                {{ selectedSectionRecord.name }}
                                <template v-if="selectedSectionRecord.grade_level_id && gradeNameById[Number(selectedSectionRecord.grade_level_id)]">
                                    · {{ gradeNameById[Number(selectedSectionRecord.grade_level_id)] }}
                                </template>
                            </template>
                            <template v-else>Section schedule</template>
                            · Monday to Sunday
                        </CardDescription>
                    </div>
                    <Badge variant="outline" class="font-mono text-[11px]">
                        {{ DAYS.reduce((total, day) => total + (weekly[day.key]?.length ?? 0), 0) }} entries
                    </Badge>
                </div>
            </CardHeader>

            <CardContent>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                    <section
                        v-for="day in DAYS"
                        :key="day.key"
                        class="flex min-h-32 flex-col overflow-hidden rounded-2xl border border-border/60 bg-card/50"
                    >
                        <header class="flex items-center justify-between border-b border-border/60 px-3 py-2">
                            <p class="text-sm font-semibold">{{ day.short }}</p>
                            <span v-if="weekly[day.key]?.length" class="font-mono text-[10px] text-muted-foreground">
                                {{ weekly[day.key].length }}
                            </span>
                        </header>

                        <div class="flex flex-1 flex-col gap-2 p-2">
                            <template v-if="weekly[day.key]?.length">
                                <article
                                    v-for="entry in weekly[day.key]"
                                    :key="entry.id"
                                    class="rounded-xl border border-primary/15 bg-primary/5 px-2.5 py-2"
                                >
                                    <p class="font-mono text-[10px] font-medium uppercase tracking-wide text-primary">
                                        {{ formatTime(entry.start_time) }}{{ entry.end_time ? ` – ${formatTime(entry.end_time)}` : '' }}
                                    </p>
                                    <p class="mt-0.5 break-words text-[13px] font-medium leading-tight" :title="entry.subject?.name ?? ''">
                                        {{ entry.subject?.name ?? '—' }}
                                    </p>
                                    <p class="mt-0.5 break-words text-[11px] leading-snug text-muted-foreground" :title="entry.teacher?.name ?? ''">
                                        {{ entry.teacher?.name ?? 'Unassigned' }}
                                    </p>
                                    <p class="break-words text-[11px] leading-snug text-muted-foreground">
                                        {{ entry.room?.name ?? 'No room' }}
                                    </p>

                                    <div class="mt-2 flex items-center gap-1 border-t border-border/40 pt-1.5">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 w-6 shrink-0 p-0"
                                            :title="`Edit ${entry.subject?.name ?? 'entry'}`"
                                            @click="openEdit(entry)"
                                        >
                                            <Pencil class="h-3 w-3" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-6 w-6 shrink-0 p-0 text-destructive hover:text-destructive"
                                            title="Delete entry"
                                            @click="handleDelete(entry)"
                                        >
                                            <Trash2 class="h-3 w-3" />
                                        </Button>
                                    </div>
                                </article>
                            </template>
                            <div
                                v-else
                                class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-border/60"
                                :style="{ minHeight: `${busiest * 4.75}rem` }"
                            >
                                <span class="text-[11px] text-muted-foreground/60">No classes</span>
                            </div>
                        </div>
                    </section>
                </div>
            </CardContent>
        </Card>

        <FormDialog
            :open="dialogOpen"
            :title="editingEntry ? 'Edit schedule entry' : 'New schedule entry'"
            description="Schedules are validated against teacher and room conflicts before saving."
            :fields="dialogFields"
            :options="dialogOptions"
            :initial-values="effectiveInitialValues"
            :submitting="submitting"
            :field-errors="fieldErrors"
            :is-editing="Boolean(editingEntry)"
            submit-label="Save schedule"
            @update:open="handleDialogClose"
            @submit="handleSave"
        />

        <p v-if="formError" class="rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
            {{ formError }}
        </p>
    </div>
</template>
