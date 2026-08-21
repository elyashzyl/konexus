<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { APP_ROUTES, AUTH_ROUTES } from '@/constants/app';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import EnrollmentAgreement from '@/pages/EnrollmentAgreement.vue';
import EnrollmentChinese from '@/pages/EnrollmentChinese.vue';
import EnrollmentFamily from '@/pages/EnrollmentFamily.vue';
import EnrollmentMedical from '@/pages/EnrollmentMedical.vue';
import EnrollmentOtherDetails from '@/pages/EnrollmentOtherDetails.vue';
import EnrollmentSignature from '@/pages/EnrollmentSignature.vue';
import EnrollmentSiblings from '@/pages/EnrollmentSiblings.vue';
import EnrollmentStudentInfo from '@/pages/EnrollmentStudentInfo.vue';
import EnrollmentTuition from '@/pages/EnrollmentTuition.vue';
import { ArrowLeft, CalendarDays, Check, CheckCircle2, LoaderCircle, ShieldCheck, Sparkles } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { toast } from 'vue-sonner';

const ENROLLMENT_STORAGE_KEY = 'konexus_enrollment_app';

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
    expires_at?: string;
}

interface SignatureRecord {
    role: string;
    signer_name: string;
    signature_data: string;
}

interface ResumeData {
    application: Application & {
        academic_year_id: number;
        department: string;
        strand: string | null;
        track: string;
        incoming_level: string;
        email: string;
        mobile_number: string;
        status: string;
        application_expires_at: string;
    };
    student: Record<string, unknown> | null;
    family: Record<string, unknown> | null;
    siblings: Record<string, unknown>[] | null;
    tuition_plan: string | null;
    medical_history: Record<string, unknown> | null;
    chinese_details: Record<string, unknown> | null;
    agreement: Record<string, unknown> | null;
    signatures: SignatureRecord[] | null;
    account_settings: Record<string, unknown> | null;
}

const steps = [
    { number: 1, label: 'Enrollment Details', group: 1 },
    { number: 2, label: 'Student Details', group: 2 },
    { number: 3, label: 'Contact Information', group: 3 },
    { number: 4, label: 'Siblings', group: 4 },
    { number: 5, label: 'School Fees Plan', group: 4 },
    { number: 6, label: 'Medical History', group: 2 },
    { number: 7, label: 'Chinese Class', group: 4 },
    { number: 8, label: 'Other Details', group: 4 },
    { number: 9, label: 'Agreements', group: 5 },
    { number: 10, label: 'Signature', group: 5 },
];

const sections = [
    { number: 1, label: 'Enrollment Details' },
    { number: 2, label: 'Student Details' },
    { number: 3, label: 'Contact Information' },
    { number: 4, label: 'Other Details' },
    { number: 5, label: 'Attachments' },
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

const statuses = [
    { value: 'new', label: 'New' },
    { value: 'continuing', label: 'Continuing' },
    { value: 'returning', label: 'Returning' },
    { value: 'transferee', label: 'Transferee' },
];

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
const accountSettings = ref<Record<string, unknown> | null>(null);
const completed = ref(false);
const paymentMethod = ref<'online' | 'cash' | ''>('');
const paymentSaving = ref(false);

async function choosePayment(method: 'online' | 'cash'): Promise<void> {
    if (!application.value || paymentSaving.value) return;
    paymentSaving.value = true;
    try {
        await api.post(`/public/enrollments/${application.value.id}/payment-preference`, { payment_method: method });
        paymentMethod.value = method;
        toast.success(method === 'online' ? 'Online payment selected.' : 'Cash payment selected.');
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        paymentSaving.value = false;
    }
}

const resumeAvailable = ref(false);
const resuming = ref(false);
const today = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });

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

const currentGroup = computed(() => steps.find((item) => item.number === step.value)?.group ?? 1);

const heroCopy = computed(() => {
    if (completed.value) {
        return {
            eyebrow: 'Online Enrollment · Complete',
            title: 'Application submitted',
            description: 'Your enrollment application is ready for the next step. We will contact you for the remaining requirements.',
        };
    }

    const meta = sections.find((item) => item.number === currentGroup.value) ?? sections[0];

    return {
        eyebrow: `Online Enrollment · Section ${meta.number} of 5`,
        title: meta.label,
        description:
            step.value === 1
                ? 'Official enrollment information for this school year. Identifiers and registrar statuses are recorded automatically.'
                : 'Complete each section to finish your application. You can leave and come back anytime.',
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
    await loadOptions();
    await checkResume();
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

const checkResume = async () => {
    const raw = localStorage.getItem(ENROLLMENT_STORAGE_KEY);

    if (!raw) {
        return;
    }

    let stored: Application;

    try {
        stored = JSON.parse(raw) as Application;
    } catch {
        localStorage.removeItem(ENROLLMENT_STORAGE_KEY);
        return;
    }

    try {
        const response = await api.get<{ data: ResumeData }>(`/public/enrollments/${stored.id}`);
        const data = response.data.data;

        resumeAvailable.value = true;
        application.value = {
            id: data.application.id,
            reference_number: data.application.reference_number,
            expires_at: data.application.application_expires_at,
        };
        track.value = data.application.track;
        student.value = data.student;
        family.value = data.family;
        siblings.value = data.siblings ?? [];
        tuitionPlan.value = data.tuition_plan;
        medicalHistory.value = data.medical_history;
        chineseDetails.value = data.chinese_details;
        agreement.value = data.agreement;
        signatures.value = data.signatures ?? [];
        accountSettings.value = data.account_settings;

        const studentSigned = signatures.value.some((signature) => signature.role === 'student');
        const parentSigned = signatures.value.some((signature) => signature.role === 'parent');

        if (studentSigned && parentSigned) {
            completed.value = true;
        } else {
            step.value = computeResumeStep();
        }
    } catch {
        localStorage.removeItem(ENROLLMENT_STORAGE_KEY);
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
    if (accountSettings.value) next = 9;
    if (agreement.value?.registration_consent === true) next = 10;

    return next;
};

const resume = () => {
    resuming.value = true;

    if (completed.value) {
        completed.value = false;
        step.value = 10;
    } else {
        step.value = computeResumeStep();
    }

    resuming.value = false;
};

const startNew = () => {
    localStorage.removeItem(ENROLLMENT_STORAGE_KEY);
    application.value = null;
    track.value = '';
    student.value = null;
    family.value = null;
    siblings.value = [];
    tuitionPlan.value = null;
    medicalHistory.value = null;
    chineseDetails.value = null;
    agreement.value = null;
    signatures.value = [];
    accountSettings.value = null;
    completed.value = false;
    resumeAvailable.value = false;
    step.value = 1;
    form.value = {
        school_profile_id: options.value.school_id ? String(options.value.school_id) : '',
        campus_id: options.value.campus_id ? String(options.value.campus_id) : '',
        academic_year_id: options.value.academic_years.length === 1 ? String(options.value.academic_years[0].id) : '',
        department: '',
        strand: '',
        status: '',
        incoming_level: '',
        track: '',
        email: '',
        mobile_number: '',
    };
};

const goBack = () => {
    if (step.value <= 1) {
        return;
    }

    if (step.value === 8 && !chineseApplicable.value) {
        step.value = 6;
        return;
    }

    if (step.value === 9 && !chineseApplicable.value) {
        step.value = 8;
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
        const response = await api.post<{ data: Application }>('/public/enrollments', {
            ...form.value,
            school_profile_id: Number(form.value.school_profile_id),
            campus_id: Number(form.value.campus_id),
            academic_year_id: Number(form.value.academic_year_id),
        });
        application.value = {
            id: response.data.data.id,
            reference_number: response.data.data.reference_number,
            expires_at: response.data.data.expires_at,
        };
        track.value = form.value.track;
        localStorage.setItem(ENROLLMENT_STORAGE_KEY, JSON.stringify(application.value));
        step.value = 2;
        toast.success('Application details saved.');
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

const onOtherDetailsSubmitted = (data: { account_settings: Record<string, unknown> }) => {
    accountSettings.value = data.account_settings;
    step.value = 9;
};

const onAgreementSubmitted = (data: { agreement: Record<string, unknown> }) => {
    agreement.value = data.agreement;
    step.value = 10;
};

const onSignatureSubmitted = () => {
    completed.value = true;
};

const formatExpiry = (iso: string): string => {
    return new Date(iso).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
};
</script>

<template>
    <div class="relative min-h-svh overflow-x-clip bg-background">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-[42rem] bg-[radial-gradient(60rem_28rem_at_50%_-24%,hsl(26_57%_40%/0.12),transparent)]"
        />

        <!-- Navigation -->
        <header class="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-5 sm:px-8">
                <RouterLink :to="APP_ROUTES.landing.path" class="flex items-center gap-2.5">
                    <AppLogoIcon class="size-8 rounded-md" />
                    <span
                        class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)] bg-clip-text font-display text-lg font-semibold tracking-[-0.01em] text-transparent"
                    >
                        KONEXUS
                    </span>
                </RouterLink>

                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" as-child>
                        <RouterLink :to="AUTH_ROUTES.login.path">Log in</RouterLink>
                    </Button>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative mx-auto max-w-3xl px-5 pb-20 pt-14 sm:px-8 sm:pt-16">
            <div class="portal-rise text-center">
                <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">{{ heroCopy.eyebrow }}</p>
                <h1 class="mt-5 font-display text-4xl font-medium leading-[1.05] tracking-[-0.02em] text-foreground sm:text-5xl">
                    {{ heroCopy.title }}
                </h1>
                <p class="mx-auto mt-4 max-w-xl text-[15px] leading-7 text-muted-foreground">{{ heroCopy.description }}</p>
            </div>

            <!-- Step indicator -->
            <div v-if="!completed" class="portal-rise mt-8 flex flex-wrap items-center justify-center gap-x-2 gap-y-3 sm:gap-x-3" style="animation-delay: 80ms">
                <template v-for="(item, index) in sections" :key="item.number">
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div
                            class="flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                            :class="currentGroup === item.number ? 'bg-primary/10 text-primary ring-1 ring-primary/20' : 'text-muted-foreground'"
                        >
                            <span
                                class="flex size-5 items-center justify-center rounded-full"
                                :class="currentGroup > item.number ? 'bg-primary text-primary-foreground' : currentGroup === item.number ? 'bg-primary/15' : 'bg-border/60'"
                            >
                                <Check v-if="currentGroup > item.number" class="size-3" />
                                <template v-else>{{ item.number }}</template>
                            </span>
                            <span class="hidden sm:inline">{{ item.label }}</span>
                        </div>
                        <span v-if="index < sections.length - 1" class="h-px w-2 bg-border sm:w-6" />
                    </div>
                </template>
            </div>

            <!-- Resume banner -->
            <div v-if="resumeAvailable && !completed" class="portal-rise mt-6" style="animation-delay: 120ms">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary/15 bg-primary/5 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <Sparkles class="size-5 shrink-0 text-primary" />
                        <p class="text-sm text-muted-foreground">
                            You have an application in progress — <span class="font-mono font-medium text-foreground">{{ application?.reference_number }}</span>.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button size="sm" class="h-8 gap-2" :disabled="resuming" @click="resume">
                            <LoaderCircle v-if="resuming" class="size-3.5 animate-spin" />
                            Continue
                        </Button>
                        <Button size="sm" variant="ghost" class="h-8" @click="startNew">Start new</Button>
                    </div>
                </div>
            </div>

            <!-- Back navigation -->
            <div v-if="step > 1 && !completed" class="portal-rise mt-6" style="animation-delay: 140ms">
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
                            <CardTitle>Enrollment information</CardTitle>
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

                            <div class="grid gap-3 rounded-xl border border-border/60 bg-muted/20 p-4">
                                <p class="text-sm font-medium text-foreground">Enrollment status</p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="grid gap-1">
                                        <span class="text-xs text-muted-foreground">Date enrolled</span>
                                        <span class="text-sm">{{ today }}</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="text-xs text-muted-foreground">Officially enrolled</span>
                                        <span class="text-sm">Pending registrar approval</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="text-xs text-muted-foreground">Student withdrawn status</span>
                                        <span class="text-sm">Not withdrawn</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="text-xs text-muted-foreground">Sanctioned status</span>
                                        <span class="text-sm">Clear</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="text-xs text-muted-foreground">Date withdrawn</span>
                                        <span class="text-sm">—</span>
                                    </div>
                                    <div class="grid gap-1">
                                        <span class="text-xs text-muted-foreground">Initial payment status</span>
                                        <span class="text-sm">Unpaid until assessment</span>
                                    </div>
                                    <div class="grid gap-1 sm:col-span-2">
                                        <span class="text-xs text-muted-foreground">Online enrollment reference number</span>
                                        <span class="font-mono text-sm">{{ application?.reference_number ?? 'Issued after this step' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 rounded-xl border border-primary/15 bg-primary/5 p-4">
                                <ShieldCheck class="mt-0.5 size-5 shrink-0 text-primary" />
                                <p class="text-sm leading-6 text-muted-foreground">
                                    <span class="font-medium text-foreground">Data Privacy Act (R.A. 10173).</span>
                                    The information you provide is collected solely for enrollment processing, is securely stored, and will not be
                                    shared without your consent. If you do not pursue your enrollment, your information is automatically deleted
                                    after 30 days.
                                </p>
                            </div>

                            <Button type="submit" class="h-10" :disabled="processing || optionsLoading">
                                <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                                {{ processing ? 'Submitting…' : 'Continue to student information' }}
                            </Button>
                        </CardContent>
                    </form>
                </Card>
            </div>

            <!-- Part 2 – Student information -->
            <div v-else-if="step === 2 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                <EnrollmentStudentInfo :application="application" :initial-student="student" @submitted="onStudentSubmitted" />
            </div>

            <!-- Part 3 – Family background -->
            <div v-else-if="step === 3 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                <EnrollmentFamily :application="application" :initial-family="family" @submitted="onFamilySubmitted" />
            </div>

            <!-- Part 4 – Siblings -->
            <div v-else-if="step === 4 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                <EnrollmentSiblings :application="application" :initial-siblings="siblings" @submitted="onSiblingsSubmitted" />
            </div>

            <!-- Part 5 – School fees plan -->
            <div v-else-if="step === 5 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                <EnrollmentTuition :application="application" :initial-tuition-plan="tuitionPlan" @submitted="onTuitionSubmitted" />
            </div>

            <!-- Part 6 – Medical history -->
            <div v-else-if="step === 6 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                <EnrollmentMedical :application="application" :initial-medical="medicalHistory" @submitted="onMedicalSubmitted" />
            </div>

            <!-- Part 7 – Chinese class -->
            <div v-else-if="step === 7 && application && chineseApplicable" class="portal-rise mt-8" style="animation-delay: 160ms">
                <EnrollmentChinese
                    :application="application"
                    :levels="levelNames"
                    :initial-chinese="chineseDetails"
                    @submitted="onChineseSubmitted"
                />
            </div>

            <!-- Other details -->
            <div v-else-if="step === 8 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                <EnrollmentOtherDetails :application="application" :initial-settings="accountSettings" @submitted="onOtherDetailsSubmitted" />
            </div>

            <!-- Attachments – School agreement -->
            <div v-else-if="step === 9 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                <EnrollmentAgreement :application="application" :initial-agreement="agreement" @submitted="onAgreementSubmitted" />
            </div>

            <!-- Attachments – Signatures -->
            <div v-else-if="step === 10 && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                <EnrollmentSignature
                    :application="application"
                    :student-name="studentName"
                    :parent-name="parentName"
                    :initial-signatures="signatures"
                    @submitted="onSignatureSubmitted"
                />
            </div>

            <!-- Completion -->
            <div v-if="completed && application" class="portal-rise mt-8" style="animation-delay: 160ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader class="items-center text-center">
                        <span class="flex size-12 items-center justify-center rounded-full bg-primary/10 ring-1 ring-primary/15">
                            <CheckCircle2 class="size-6 text-primary" />
                        </span>
                        <CardTitle class="pt-3 text-2xl">Enrollment application submitted</CardTitle>
                        <CardDescription>
                            All the required information for your enrollment application has been submitted.
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
                                <span class="text-sm font-medium capitalize text-foreground">Pending</span>
                            </div>
                            <div v-if="application.expires_at" class="flex items-center justify-between rounded-xl border border-border/60 bg-background px-4 py-3">
                                <span class="text-sm text-muted-foreground">Application expires</span>
                                <span class="text-sm font-medium text-foreground">{{ formatExpiry(application.expires_at) }}</span>
                            </div>
                            <p class="pt-2 text-center text-sm leading-6 text-muted-foreground">
                                Settle the initial tuition online or in cash at the school. Accounting will mark the record paid, then the
                                principal will assign the learner to a section.
                            </p>
                            <div class="grid grid-cols-2 gap-2">
                                <Button variant="outline" :disabled="paymentSaving" @click="choosePayment('online')">
                                    {{ paymentMethod === 'online' ? 'Online selected' : 'Pay online' }}
                                </Button>
                                <Button variant="outline" :disabled="paymentSaving" @click="choosePayment('cash')">
                                    {{ paymentMethod === 'cash' ? 'Cash selected' : 'Pay in cash' }}
                                </Button>
                            </div>
                            <div class="flex gap-3">
                                <Button variant="outline" class="flex-1" @click="completed = false; step = 9">Review</Button>
                                <Button class="flex-1" @click="startNew">Start a new application</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <p class="mt-6 text-center text-sm text-muted-foreground">
                Already enrolled or part of our school?
                <RouterLink :to="AUTH_ROUTES.login.path" class="font-medium text-primary underline underline-offset-4">Log in</RouterLink>
            </p>
        </section>
    </div>
</template>