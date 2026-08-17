<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ENROLLMENT_TYPES } from '@/modules/foundation/config';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { useWorkspaceStore } from '@/stores/workspace';
import EnrollmentAgreement from '@/pages/EnrollmentAgreement.vue';
import EnrollmentChinese from '@/pages/EnrollmentChinese.vue';
import EnrollmentFamily from '@/pages/EnrollmentFamily.vue';
import EnrollmentMedical from '@/pages/EnrollmentMedical.vue';
import EnrollmentSignature from '@/pages/EnrollmentSignature.vue';
import EnrollmentSiblings from '@/pages/EnrollmentSiblings.vue';
import EnrollmentStudentInfo from '@/pages/EnrollmentStudentInfo.vue';
import EnrollmentTuition from '@/pages/EnrollmentTuition.vue';
import { ArrowLeft, ArrowRight, CalendarDays, Check, CheckCircle2, ClipboardList, LoaderCircle, ShieldCheck } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { toast } from 'vue-sonner';

const API_BASE = '/enrollments';

interface AcademicYear {
    id: number;
    name: string;
    code: string;
    start_date: string;
    end_date: string;
}

interface GradeLevel {
    id: number;
    name: string;
    code: string;
    short_name: string;
    education_level: string;
}

interface SchoolOption {
    id: number;
    name: string;
    short_name: string | null;
}

interface CampusOption {
    id: number;
    name: string;
    code: string | null;
    address: string | null;
}

interface EnrollmentOptions {
    schools: SchoolOption[];
    school_id: number | null;
    campuses: CampusOption[];
    campus_id: number | null;
    academic_years: AcademicYear[];
    grade_levels: GradeLevel[];
}

interface Application {
    id: number;
    reference_number: string;
    status: string;
}

interface SignatureRecord {
    role: string;
    signer_name: string;
    signature_data: string;
}

interface ResumeData {
    application: Application & {
        school_profile_id: number;
        campus_id: number;
        academic_year_id: number;
        department: string;
        strand: string | null;
        track: string;
        incoming_level: string;
        email: string;
        mobile_number: string;
        enrollment_type: string;
        application_expires_at: string | null;
    };
    student: Record<string, unknown> | null;
    family: Record<string, unknown> | null;
    siblings: Record<string, unknown>[] | null;
    tuition_plan: string | null;
    medical_history: Record<string, unknown> | null;
    chinese_details: Record<string, unknown> | null;
    agreement: Record<string, unknown> | null;
    signatures: SignatureRecord[] | null;
}

const steps = [
    { number: 1, label: 'Application' },
    { number: 2, label: 'Student Information' },
    { number: 3, label: 'Family Background' },
    { number: 4, label: 'Siblings' },
    { number: 5, label: 'School Fees Plan' },
    { number: 6, label: 'Medical History' },
    { number: 7, label: 'Chinese Class' },
    { number: 8, label: 'Agreement' },
    { number: 9, label: 'Signature' },
];

const departments = [
    { value: 'pre-school', label: 'Pre School' },
    { value: 'grade-school', label: 'Grade School' },
    { value: 'junior-high', label: 'Junior High School' },
    { value: 'senior-high', label: 'Senior High School' },
];

const strands = [
    { value: 'stem', label: 'Science, Technology, Engineering & Mathematics (STEM)' },
    { value: 'abm', label: 'Accountancy, Business & Management (ABM)' },
    { value: 'humss', label: 'Humanities & Social Sciences (HUMSS)' },
    { value: 'gas', label: 'General Academic Strand (GAS)' },
    { value: 'tvl', label: 'Technical-Vocational-Livelihood (TVL)' },
];

const statuses: { value: string; label: string }[] = ENROLLMENT_TYPES.map((option) => ({
    value: String(option.value),
    label: option.label,
}));

const tracks = [
    { value: 'english', label: 'English' },
    { value: 'chinese', label: 'Chinese' },
    { value: 'integrated', label: 'Integrated' },
];

const departmentLevelFilters: Record<string, string[]> = {
    'pre-school': ['pre-school', 'kindergarten', 'early-childhood'],
    'grade-school': ['elementary', 'grade-school', 'primary'],
    'junior-high': ['junior-high'],
    'senior-high': ['senior-high'],
};

const route = useRoute();
const workspace = useWorkspaceStore();

const options = ref<EnrollmentOptions>({
    schools: [],
    school_id: null,
    campuses: [],
    campus_id: null,
    academic_years: [],
    grade_levels: [],
});
const optionsLoading = ref(true);

const form = ref({
    school_profile_id: '',
    campus_id: '',
    academic_year_id: '',
    department: '',
    strand: '',
    status: '',
    incoming_level: '',
    track: '',
    email: '',
    mobile_number: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const step = ref(1);
const application = ref<Application | null>(null);
const track = ref('');
const student = ref<Record<string, unknown> | null>(null);
const family = ref<Record<string, unknown> | null>(null);
const siblings = ref<Record<string, unknown>[]>([]);
const tuitionPlan = ref<string | null>(null);
const medicalHistory = ref<Record<string, unknown> | null>(null);
const chineseDetails = ref<Record<string, unknown> | null>(null);
const agreement = ref<Record<string, unknown> | null>(null);
const signatures = ref<SignatureRecord[]>([]);
const completed = ref(false);
const submittingApproval = ref(false);
const submittedForApproval = ref(false);
const today = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });

const enrollmentDataHref = computed(() => {
    const portalMatch = route.path.match(/^\/portal\/staff\/[^/]+/);

    return portalMatch ? `${portalMatch[0]}/enrollments` : '/school/enrollments';
});

const chineseMandatory = computed(() => ['pre-school', 'grade-school'].includes(form.value.department));
const showStrand = computed(() => form.value.department === 'senior-high');
const chineseApplicable = computed(() => track.value === 'chinese');

const levelOptions = computed<GradeLevel[]>(() => {
    const filters = departmentLevelFilters[form.value.department];

    if (!filters) {
        return options.value.grade_levels;
    }

    const matches = options.value.grade_levels.filter((level) => filters.includes(level.education_level));

    return matches.length ? matches : options.value.grade_levels;
});

const levelNames = computed(() => options.value.grade_levels.map((level) => level.name));

const studentName = computed(() => {
    const data = student.value;

    if (!data) {
        return '';
    }

    return [data.first_name, data.middle_name, data.last_name].filter(Boolean).join(' ').trim();
});

const parentName = computed(() => {
    const data = family.value;

    if (!data) {
        return '';
    }

    const guardian = data.guardian as Record<string, unknown> | null | undefined;
    if (guardian && (guardian.first_name || guardian.last_name)) {
        return [guardian.first_name, guardian.middle_name, guardian.last_name].filter(Boolean).join(' ').trim();
    }

    const father = data.father as Record<string, unknown> | null | undefined;
    if (father && (father.first_name || father.last_name)) {
        return [father.first_name, father.last_name].filter(Boolean).join(' ').trim();
    }

    const mother = data.mother as Record<string, unknown> | null | undefined;
    if (mother && (mother.first_name || mother.last_name)) {
        return [mother.first_name, mother.last_name].filter(Boolean).join(' ').trim();
    }

    return '';
});

const heroCopy = computed(() => {
    if (submittedForApproval.value) {
        return {
            eyebrow: 'Walk-in Enrollment · Submitted',
            title: 'Submitted for approval',
            description: 'The enrollment is now in the principal review queue and can be tracked from the enrollment data page.',
        };
    }

    if (completed.value) {
        return {
            eyebrow: 'Walk-in Enrollment · Complete',
            title: 'Enrollment details collected',
            description: 'Review the reference number below, then submit the enrollment for approval to start the workflow.',
        };
    }

    const meta = steps[step.value - 1];

    return {
        eyebrow: `Walk-in Enrollment · Part ${meta.number}`,
        title: meta.label,
        description:
            step.value === 1
                ? "Set up the application details for the family being enrolled at the school."
                : 'Complete each part to finish the enrollment. You can leave and come back anytime.',
    };
});

watch(
    () => form.value.school_profile_id,
    async (schoolId, previousSchoolId) => {
        if (!schoolId || schoolId === previousSchoolId) {
            return;
        }

        form.value.campus_id = '';
        form.value.academic_year_id = '';
        form.value.incoming_level = '';
        await loadOptions({ school_profile_id: Number(schoolId) });
    },
);

watch(
    () => form.value.campus_id,
    async (campusId, previousCampusId) => {
        if (!campusId || campusId === previousCampusId) {
            return;
        }

        form.value.academic_year_id = '';
        form.value.incoming_level = '';
        await loadOptions({
            school_profile_id: Number(form.value.school_profile_id),
            campus_id: Number(campusId),
        });
    },
);

watch(
    () => form.value.department,
    (department) => {
        if (['pre-school', 'grade-school'].includes(department)) {
            form.value.track = 'chinese';
        }

        if (department !== 'senior-high') {
            form.value.strand = '';
        }

        if (!levelOptions.value.some((level) => level.name === form.value.incoming_level)) {
            form.value.incoming_level = '';
        }
    },
);

onMounted(async () => {
    await workspace.initialize();
    await loadOptions();

    if (route.query.id) {
        await resumeById(Number(route.query.id));
    }
});

const loadOptions = async (params: { school_profile_id?: number; campus_id?: number } = {}) => {
    optionsLoading.value = true;

    try {
        const response = await api.get<{ data: EnrollmentOptions }>('/public/enrollment/options', { params });
        options.value = response.data.data;

        if (options.value.school_id && !form.value.school_profile_id) {
            form.value.school_profile_id = String(options.value.school_id);
        }

        if (options.value.campus_id && !form.value.campus_id) {
            form.value.campus_id = String(options.value.campus_id);
        }

        if (options.value.academic_years.length === 1 && !form.value.academic_year_id) {
            form.value.academic_year_id = String(options.value.academic_years[0].id);
        }
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        optionsLoading.value = false;
    }
};

const resumeById = async (id: number) => {
    optionsLoading.value = true;

    try {
        const response = await api.get<{ data: ResumeData }>(`${API_BASE}/${id}/application`);
        const data = response.data.data;

        application.value = {
            id: data.application.id,
            reference_number: data.application.reference_number,
            status: data.application.status,
        };
        track.value = data.application.track;
        form.value = {
            school_profile_id: String(data.application.school_profile_id),
            campus_id: String(data.application.campus_id),
            academic_year_id: String(data.application.academic_year_id),
            department: data.application.department,
            strand: data.application.strand ?? '',
            status: data.application.enrollment_type,
            incoming_level: data.application.incoming_level,
            track: data.application.track,
            email: data.application.email,
            mobile_number: data.application.mobile_number,
        };
        student.value = data.student;
        family.value = data.family;
        siblings.value = data.siblings ?? [];
        tuitionPlan.value = data.tuition_plan;
        medicalHistory.value = data.medical_history;
        chineseDetails.value = data.chinese_details;
        agreement.value = data.agreement;
        signatures.value = data.signatures ?? [];

        const studentSigned = signatures.value.some((signature) => signature.role === 'student');
        const parentSigned = signatures.value.some((signature) => signature.role === 'parent');

        if (studentSigned && parentSigned) {
            completed.value = true;
        } else {
            step.value = computeResumeStep();
        }
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        optionsLoading.value = false;
    }
};

const computeResumeStep = (): number => {
    let next = 2;

    if (student.value) next = 3;
    if (family.value && (family.value.father || family.value.mother || family.value.guardian)) next = 4;
    if (siblings.value.length) next = 5;
    if (tuitionPlan.value) next = 6;
    if (medicalHistory.value) next = chineseApplicable.value ? 7 : 8;
    if (chineseApplicable.value && chineseDetails.value) next = 8;
    if (agreement.value?.registration_consent === true) next = 9;

    return next;
};

const goBack = () => {
    if (step.value <= 1) {
        return;
    }

    if (step.value === 8 && !chineseApplicable.value) {
        step.value = 6;
        return;
    }

    step.value -= 1;
};

const validate = (): Record<string, string> => {
    const nextErrors: Record<string, string> = {};

    if (!form.value.school_profile_id) nextErrors.school_profile_id = 'Please choose a school.';
    if (!form.value.campus_id) nextErrors.campus_id = 'Please choose a campus.';
    if (!form.value.academic_year_id) nextErrors.academic_year_id = 'Please choose a school year.';
    if (!form.value.department) nextErrors.department = 'Please choose a department.';
    if (showStrand.value && !form.value.strand) nextErrors.strand = 'Please choose a strand.';
    if (!form.value.status) nextErrors.status = 'Please choose an enrollment status.';
    if (!form.value.incoming_level) nextErrors.incoming_level = 'Please choose an incoming level.';
    if (!form.value.track) nextErrors.track = 'Please choose a track.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) nextErrors.email = 'Please enter a valid email address.';
    if (!/^\+?[0-9][0-9 ()-]{7,18}$/.test(form.value.mobile_number)) nextErrors.mobile_number = 'Please enter a valid mobile number.';

    return nextErrors;
};

const submitPart1 = async () => {
    errors.value = validate();

    if (Object.keys(errors.value).length) {
        return;
    }

    processing.value = true;

    try {
        const response = await api.post<{ data: Application }>(`${API_BASE}/apply`, {
            ...form.value,
            school_profile_id: Number(form.value.school_profile_id),
            campus_id: Number(form.value.campus_id),
            academic_year_id: Number(form.value.academic_year_id),
        });
        application.value = {
            id: response.data.data.id,
            reference_number: response.data.data.reference_number,
            status: response.data.data.status,
        };
        track.value = form.value.track;
        step.value = 2;
        toast.success('Enrollment draft created.');
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(
            Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']),
        );
    } finally {
        processing.value = false;
    }
};

const onStudentSubmitted = (data: { student: Record<string, unknown> }) => {
    student.value = data.student;
    step.value = 3;
};

const onFamilySubmitted = (data: { family: Record<string, unknown> }) => {
    family.value = data.family;
    step.value = 4;
};

const onSiblingsSubmitted = (data: { siblings: Record<string, unknown>[] }) => {
    siblings.value = data.siblings;
    step.value = 5;
};

const onTuitionSubmitted = (data: { tuition_plan: string }) => {
    tuitionPlan.value = data.tuition_plan;
    step.value = 6;
};

const onMedicalSubmitted = (data: { medical_history: Record<string, unknown> }) => {
    medicalHistory.value = data.medical_history;
    step.value = chineseApplicable.value ? 7 : 8;
};

const onChineseSubmitted = (data: { chinese_details: Record<string, unknown> }) => {
    chineseDetails.value = data.chinese_details;
    step.value = 8;
};

const onAgreementSubmitted = (data: { agreement: Record<string, unknown> }) => {
    agreement.value = data.agreement;
    step.value = 9;
};

const onSignatureSubmitted = () => {
    completed.value = true;
};

const submitForApproval = async () => {
    if (!application.value) {
        return;
    }

    submittingApproval.value = true;

    try {
        const response = await api.post<{ data: { status: string } }>(`${API_BASE}/${application.value.id}/submit`);
        application.value.status = response.data.data.status;
        submittedForApproval.value = true;
        toast.success('Enrollment submitted for approval.');
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        submittingApproval.value = false;
    }
};
</script>

<template>
    <div class="relative min-h-full overflow-x-clip">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-[42rem] bg-[radial-gradient(60rem_28rem_at_50%_-24%,hsl(26_57%_40%/0.12),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12">
            <div class="mx-auto max-w-3xl">
                <div class="flex items-center justify-between gap-4">
                    <Button variant="ghost" size="sm" class="h-8 gap-1.5 text-muted-foreground" as-child>
                        <RouterLink :to="enrollmentDataHref">
                            <ArrowLeft class="size-4" />
                            Enrollment data
                        </RouterLink>
                    </Button>
                    <span class="inline-flex items-center gap-2 rounded-full border border-border/60 bg-card/60 px-3 py-1 text-xs text-muted-foreground">
                        <ClipboardList class="size-3.5 text-primary" />
                        Walk-in wizard
                    </span>
                </div>

                <!-- Hero -->
                <section class="pt-12">
                    <div class="portal-rise text-center">
                        <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">{{ heroCopy.eyebrow }}</p>
                        <h1 class="mt-5 font-display text-4xl font-medium leading-[1.05] tracking-[-0.02em] text-foreground sm:text-5xl">
                            {{ heroCopy.title }}
                        </h1>
                        <p class="mx-auto mt-4 max-w-xl text-[15px] leading-7 text-muted-foreground">{{ heroCopy.description }}</p>
                    </div>

                    <!-- Step indicator -->
                    <div v-if="!submittedForApproval" class="portal-rise mt-8 flex flex-wrap items-center justify-center gap-x-2 gap-y-3 sm:gap-x-3" style="animation-delay: 80ms">
                        <template v-for="(item, index) in steps" :key="item.number">
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div
                                    class="flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                                    :class="step === item.number ? 'bg-primary/10 text-primary ring-1 ring-primary/20' : 'text-muted-foreground'"
                                >
                                    <span
                                        class="flex size-5 items-center justify-center rounded-full"
                                        :class="step > item.number ? 'bg-primary text-primary-foreground' : step === item.number ? 'bg-primary/15' : 'bg-border/60'"
                                    >
                                        <Check v-if="step > item.number" class="size-3" />
                                        <template v-else>{{ item.number }}</template>
                                    </span>
                                    <span :class="item.number === 7 && !chineseApplicable ? 'hidden sm:inline line-through opacity-50' : 'hidden sm:inline'">
                                        {{ item.label }}
                                    </span>
                                </div>
                                <span v-if="index < steps.length - 1" class="h-px w-2 bg-border sm:w-6" />
                            </div>
                        </template>
                    </div>

                    <!-- Back navigation -->
                    <div v-if="step > 1 && !completed && !submittedForApproval" class="portal-rise mt-6" style="animation-delay: 140ms">
                        <Button variant="ghost" size="sm" class="h-8 gap-1.5 text-muted-foreground" @click="goBack">
                            <ArrowLeft class="size-4" />
                            Back
                        </Button>
                    </div>

                    <!-- Part 1 – Application details -->
                    <div v-if="step === 1" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <Card class="relative overflow-hidden border-border/60 bg-card/60">
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

                            <form @submit.prevent="submitPart1" class="flex flex-col gap-6">
                                <CardHeader>
                                    <CardTitle>Application details</CardTitle>
                                    <CardDescription>
                                        Fill in the necessary information below. Your email and mobile number are properly encoded and kept secure.
                                    </CardDescription>
                                </CardHeader>

                                <CardContent class="grid gap-6">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label for="school_profile_id">School</Label>
                                            <Select v-model="form.school_profile_id" :disabled="optionsLoading">
                                                <SelectTrigger id="school_profile_id" class="h-9">
                                                    <SelectValue :placeholder="optionsLoading ? 'Loading…' : 'Select school'" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="school in options.schools" :key="school.id" :value="String(school.id)">
                                                        {{ school.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.school_profile_id" />
                                        </div>

                                        <div class="grid gap-2">
                                            <Label for="campus_id">Campus</Label>
                                            <Select
                                                v-model="form.campus_id"
                                                :disabled="optionsLoading || !form.school_profile_id || options.campuses.length === 0"
                                            >
                                                <SelectTrigger id="campus_id" class="h-9">
                                                    <SelectValue
                                                        :placeholder="
                                                            optionsLoading
                                                                ? 'Loading…'
                                                                : !form.school_profile_id
                                                                  ? 'Select a school first'
                                                                  : options.campuses.length === 0
                                                                    ? 'No campuses available'
                                                                    : 'Select campus'
                                                        "
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="campus in options.campuses" :key="campus.id" :value="String(campus.id)">
                                                        {{ campus.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.campus_id" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label>Date</Label>
                                            <div class="flex h-9 items-center gap-2 rounded-md border border-border/60 bg-muted/40 px-2.5 text-sm text-muted-foreground">
                                                <CalendarDays class="size-4" />
                                                {{ today }}
                                            </div>
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="academic_year_id">School Year</Label>
                                            <Select v-model="form.academic_year_id" :disabled="optionsLoading">
                                                <SelectTrigger id="academic_year_id" class="h-9">
                                                    <SelectValue :placeholder="optionsLoading ? 'Loading…' : 'Select school year'" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="year in options.academic_years" :key="year.id" :value="String(year.id)">
                                                        {{ year.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.academic_year_id" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label for="department">Department</Label>
                                            <Select v-model="form.department">
                                                <SelectTrigger id="department" class="h-9">
                                                    <SelectValue placeholder="Select department" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="department in departments" :key="department.value" :value="department.value">
                                                        {{ department.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.department" />
                                        </div>
                                        <div v-if="showStrand" class="grid gap-2">
                                            <Label for="strand">Strand</Label>
                                            <Select v-model="form.strand">
                                                <SelectTrigger id="strand" class="h-9">
                                                    <SelectValue placeholder="Select strand" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="strand in strands" :key="strand.value" :value="strand.value">
                                                        {{ strand.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.strand" />
                                        </div>
                                        <div v-else class="grid gap-2">
                                            <Label for="status">Status</Label>
                                            <Select v-model="form.status">
                                                <SelectTrigger id="status" class="h-9">
                                                    <SelectValue placeholder="Select status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">
                                                        {{ status.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.status" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div v-if="showStrand" class="grid gap-2">
                                            <Label for="status">Status</Label>
                                            <Select v-model="form.status">
                                                <SelectTrigger id="status" class="h-9">
                                                    <SelectValue placeholder="Select status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">
                                                        {{ status.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.status" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="incoming_level">Incoming Level</Label>
                                            <Select v-model="form.incoming_level">
                                                <SelectTrigger id="incoming_level" class="h-9">
                                                    <SelectValue :placeholder="optionsLoading ? 'Loading…' : 'Select level'" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="level in levelOptions" :key="level.id" :value="level.name">
                                                        {{ level.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.incoming_level" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="track">Track</Label>
                                            <Select v-model="form.track" :disabled="chineseMandatory">
                                                <SelectTrigger id="track" class="h-9">
                                                    <SelectValue placeholder="Select track" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem v-for="track in tracks" :key="track.value" :value="track.value">
                                                        {{ track.label }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError :message="errors.track" />
                                            <p v-if="chineseMandatory" class="text-xs leading-5 text-muted-foreground">
                                                Chinese classes are mandatory for Pre School – Grade 8.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="grid gap-2">
                                            <Label for="email">Email address</Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                required
                                                autocomplete="email"
                                                v-model="form.email"
                                                placeholder="email@example.com"
                                                class="h-9 px-2.5 py-1.5"
                                            />
                                            <InputError :message="errors.email" />
                                        </div>
                                        <div class="grid gap-2">
                                            <Label for="mobile_number">Cellphone / Mobile number</Label>
                                            <Input
                                                id="mobile_number"
                                                type="tel"
                                                required
                                                autocomplete="tel"
                                                v-model="form.mobile_number"
                                                placeholder="+63 900 000 0000"
                                                class="h-9 px-2.5 py-1.5"
                                            />
                                            <InputError :message="errors.mobile_number" />
                                        </div>
                                    </div>

                                    <div class="flex gap-3 rounded-xl border border-primary/15 bg-primary/5 p-4">
                                        <ShieldCheck class="mt-0.5 size-5 shrink-0 text-primary" />
                                        <p class="text-sm leading-6 text-muted-foreground">
                                            <span class="font-medium text-foreground">Data Privacy Act (R.A. 10173).</span>
                                            The information you provide is collected solely for enrollment processing and is securely stored.
                                        </p>
                                    </div>

                                    <Button type="submit" class="h-10" :disabled="processing || optionsLoading">
                                        <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                                        {{ processing ? 'Creating draft…' : 'Continue to student information' }}
                                    </Button>
                                </CardContent>
                            </form>
                        </Card>
                    </div>

                    <!-- Part 2 – Student information -->
                    <div v-else-if="step === 2 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <EnrollmentStudentInfo
                            :application="application"
                            :initial-student="student"
                            :api-base="API_BASE"
                            @submitted="onStudentSubmitted"
                        />
                    </div>

                    <!-- Part 3 – Family background -->
                    <div v-else-if="step === 3 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <EnrollmentFamily :application="application" :initial-family="family" :api-base="API_BASE" @submitted="onFamilySubmitted" />
                    </div>

                    <!-- Part 4 – Siblings -->
                    <div v-else-if="step === 4 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <EnrollmentSiblings :application="application" :initial-siblings="siblings" :api-base="API_BASE" @submitted="onSiblingsSubmitted" />
                    </div>

                    <!-- Part 5 – School fees plan -->
                    <div v-else-if="step === 5 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <EnrollmentTuition :application="application" :initial-tuition-plan="tuitionPlan" :api-base="API_BASE" @submitted="onTuitionSubmitted" />
                    </div>

                    <!-- Part 6 – Medical history -->
                    <div v-else-if="step === 6 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <EnrollmentMedical :application="application" :initial-medical="medicalHistory" :api-base="API_BASE" @submitted="onMedicalSubmitted" />
                    </div>

                    <!-- Part 7 – Chinese class -->
                    <div v-else-if="step === 7 && application && chineseApplicable" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <EnrollmentChinese
                            :application="application"
                            :levels="levelNames"
                            :initial-chinese="chineseDetails"
                            :api-base="API_BASE"
                            @submitted="onChineseSubmitted"
                        />
                    </div>

                    <!-- Part 8 – School agreement -->
                    <div v-else-if="step === 8 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <EnrollmentAgreement :application="application" :initial-agreement="agreement" :api-base="API_BASE" @submitted="onAgreementSubmitted" />
                    </div>

                    <!-- Part 9 – Signatures -->
                    <div v-else-if="step === 9 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <EnrollmentSignature
                            :application="application"
                            :student-name="studentName"
                            :parent-name="parentName"
                            :initial-signatures="signatures"
                            :api-base="API_BASE"
                            @submitted="onSignatureSubmitted"
                        />
                    </div>

                    <!-- Completion -->
                    <div v-if="completed && !submittedForApproval && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <Card class="relative overflow-hidden border-border/60 bg-card/60">
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                            <CardHeader class="items-center text-center">
                                <span class="flex size-12 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/15">
                                    <CheckCircle2 class="size-6 text-primary" />
                                </span>
                                <CardTitle class="pt-3 text-2xl">Enrollment details collected</CardTitle>
                                <CardDescription>
                                    All the required information has been gathered for this walk-in enrollment.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="mx-auto max-w-sm space-y-3">
                                    <div class="flex items-center justify-between rounded-xl border border-border/60 bg-background px-4 py-3">
                                        <span class="text-sm text-muted-foreground">Reference number</span>
                                        <span class="font-mono text-sm font-semibold text-foreground">{{ application.reference_number }}</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl border border-border/60 bg-background px-4 py-3">
                                        <span class="text-sm text-muted-foreground">Status</span>
                                        <span class="text-sm font-medium capitalize text-foreground">{{ application.status.replaceAll('-', ' ') }}</span>
                                    </div>
                                    <p class="pt-2 text-center text-sm leading-6 text-muted-foreground">
                                        Submitting for approval moves this enrollment into the principal review queue.
                                    </p>
                                    <div class="flex gap-3">
                                        <Button variant="outline" class="flex-1" @click="completed = false; step = 9">Review</Button>
                                        <Button class="flex-1 gap-2" :disabled="submittingApproval" @click="submitForApproval">
                                            <LoaderCircle v-if="submittingApproval" class="size-4 animate-spin" />
                                            {{ submittingApproval ? 'Submitting…' : 'Submit for approval' }}
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Submitted for approval -->
                    <div v-if="submittedForApproval && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                        <Card class="relative overflow-hidden border-border/60 bg-card/60">
                            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                            <CardHeader class="items-center text-center">
                                <span class="flex size-12 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/15">
                                    <CheckCircle2 class="size-6 text-primary" />
                                </span>
                                <CardTitle class="pt-3 text-2xl">Submitted for approval</CardTitle>
                                <CardDescription>
                                    The enrollment has been submitted and now sits in the principal approval queue.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="mx-auto max-w-sm space-y-3">
                                    <div class="flex items-center justify-between rounded-xl border border-border/60 bg-background px-4 py-3">
                                        <span class="text-sm text-muted-foreground">Reference number</span>
                                        <span class="font-mono text-sm font-semibold text-foreground">{{ application.reference_number }}</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-xl border border-border/60 bg-background px-4 py-3">
                                        <span class="text-sm text-muted-foreground">Status</span>
                                        <span class="text-sm font-medium capitalize text-foreground">{{ application.status.replaceAll('-', ' ') }}</span>
                                    </div>
                                    <Button class="w-full gap-2" as-child>
                                        <RouterLink :to="enrollmentDataHref">
                                            View enrollment data
                                            <ArrowRight class="size-4" />
                                        </RouterLink>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>