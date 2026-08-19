<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Combobox } from '@/components/ui/combobox';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PH_GEO } from '@/data/ph-geo';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { CalendarDays, ImagePlus, LoaderCircle, Upload, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface Application {
    id: number;
    reference_number: string;
}

type InitialStudent = Record<string, unknown>;

const props = withDefaults(defineProps<{
    application: Application;
    initialStudent?: InitialStudent | null;
    apiBase?: string;
}>(), {
    apiBase: '/public/enrollments',
});

const emit = defineEmits<{ submitted: [data: { student: Record<string, unknown> }] }>();

const genders = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
];

const interests = [
    { value: 'academics', label: 'Academics' },
    { value: 'sports', label: 'Sports' },
    { value: 'service-and-sustainability', label: 'Service and Sustainability' },
    { value: 'arts', label: 'Arts (Art, Music, Dance, Performing)' },
];

const form = ref({
    school_student_id: '',
    lrn: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    extension_name: '',
    nickname: '',
    birth_date: '',
    gender: '',
    nationality: '',
    citizenship: '',
    religion: '',
    mobile_number: '',
    email: '',
    place_of_birth: '',
    ethnicity: '',
    is_indigenous: false,
    mother_tongue: '',
    telephone_number: '',
    current_address: '',
    current_province: '',
    current_city: '',
    current_barangay: '',
    interests: [] as string[],
});

const provinceOptions = computed(() => PH_GEO.map((entry) => ({ value: entry.province, label: entry.province })));
const cityOptions = computed(() => {
    const entry = PH_GEO.find((item) => item.province === form.value.current_province);

    return (entry?.cities ?? []).map((city) => ({ value: city, label: city }));
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const photoFile = ref<File | null>(null);
const photoPreview = ref<string | null>(null);
const photoUrl = ref<string | null>(null);
const photoUploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const age = computed(() => {
    if (!form.value.birth_date) {
        return null;
    }

    const birth = new Date(form.value.birth_date);

    if (Number.isNaN(birth.getTime())) {
        return null;
    }

    const today = new Date();
    let years = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        years -= 1;
    }

    return years;
});

watch(
    () => form.value.current_province,
    () => {
        if (!cityOptions.value.some((city) => city.value === form.value.current_city)) {
            form.value.current_city = '';
        }
    },
);

onMounted(() => {
    const student = props.initialStudent;

    if (!student) {
        return;
    }

    const pick = <T>(key: string, fallback: T): T => (student[key] as T | undefined) ?? fallback;

    form.value = {
        school_student_id: pick('school_student_id', '') ?? '',
        lrn: pick('lrn', '') ?? '',
        first_name: pick('first_name', '') ?? '',
        middle_name: pick('middle_name', '') ?? '',
        last_name: pick('last_name', '') ?? '',
        extension_name: pick('extension_name', '') ?? '',
        nickname: pick('nickname', '') ?? '',
        birth_date: pick('birth_date', '') ?? '',
        gender: pick('gender', '') ?? '',
        nationality: pick('nationality', '') ?? '',
        citizenship: pick('citizenship', '') ?? '',
        religion: pick('religion', '') ?? '',
        mobile_number: pick('mobile_number', '') ?? '',
        email: pick('email', '') ?? '',
        place_of_birth: pick('place_of_birth', '') ?? '',
        ethnicity: pick('ethnicity', '') ?? '',
        is_indigenous: Boolean(pick('is_indigenous', false)),
        mother_tongue: pick('mother_tongue', '') ?? '',
        telephone_number: pick('telephone_number', '') ?? '',
        current_address: pick('current_address', '') ?? '',
        current_province: pick('current_province', '') ?? '',
        current_city: pick('current_city', '') ?? '',
        current_barangay: pick('current_barangay', '') ?? '',
        interests: Array.isArray(student.interests) ? (student.interests as string[]) : [],
    };
    photoUrl.value = pick('profile_picture_url', null);
});

const validate = (): Record<string, string> => {
    const nextErrors: Record<string, string> = {};

    if (!form.value.first_name.trim()) nextErrors.first_name = 'First name is required.';
    if (!form.value.last_name.trim()) nextErrors.last_name = 'Last name is required.';
    if (!form.value.birth_date) nextErrors.birth_date = 'Birthdate is required.';
    if (form.value.birth_date && new Date(form.value.birth_date) >= new Date()) nextErrors.birth_date = 'Birthdate must be in the past.';
    if (!form.value.gender) nextErrors.gender = 'Please choose a gender.';
    if (form.value.lrn && !/^\d{12}$/.test(form.value.lrn)) nextErrors.lrn = 'LRN must be 12 digits.';
    if (form.value.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) nextErrors.email = 'Enter a valid email address.';

    return nextErrors;
};

const onPhotoSelected = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        toast.error('Please choose an image file.');
        return;
    }

    if (file.size > 4 * 1024 * 1024) {
        toast.error('Photo must be 4MB or smaller.');
        return;
    }

    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
};

const clearPhoto = () => {
    photoFile.value = null;
    photoPreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = async () => {
    errors.value = validate();

    if (Object.keys(errors.value).length) {
        return;
    }

    processing.value = true;

    try {
        const response = await api.put<{ data: { student: Record<string, unknown> } }>(`${props.apiBase}/${props.application.id}/student`, {
            ...form.value,
            interests: form.value.interests,
        });

        if (photoFile.value) {
            photoUploading.value = true;
            const data = new FormData();
            data.append('photo', photoFile.value);
            const photoResponse = await api.post<{ data: { profile_picture_url: string } }>(
                `${props.apiBase}/${props.application.id}/student/photo`,
                data,
            );
            photoUrl.value = photoResponse.data.data.profile_picture_url;
            photoFile.value = null;
            photoPreview.value = null;
        }

        toast.success('Student information saved.');
        emit('submitted', { student: response.data.data.student });
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(
            Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']),
        );
    } finally {
        photoUploading.value = false;
        processing.value = false;
    }
};
</script>

<template>
    <Card class="relative overflow-hidden border-border/60 bg-card/60">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

        <form @submit.prevent="submit">
            <CardHeader>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <CardTitle>Student details</CardTitle>
                        <CardDescription>Personal profile, demographics, and official identifiers.</CardDescription>
                    </div>
                    <span class="shrink-0 rounded-full bg-primary/10 px-3 py-1 font-mono text-[11px] font-medium text-primary ring-1 ring-primary/15">
                        {{ application.reference_number }}
                    </span>
                </div>
            </CardHeader>

            <CardContent class="grid gap-6">
                <!-- Student identifiers -->
                <div class="grid gap-3">
                    <p class="text-sm font-medium text-muted-foreground">Student identification</p>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label>System ID</Label>
                            <div class="flex h-9 items-center rounded-md border border-border/60 bg-muted/40 px-2.5 font-mono text-sm text-muted-foreground">
                                {{ application.id }}
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label>Student number</Label>
                            <div class="flex h-9 items-center rounded-md border border-border/60 bg-muted/40 px-2.5 font-mono text-sm text-muted-foreground">
                                {{ String(initialStudent?.student_number ?? 'Assigned on save') }}
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="school_student_id">School student ID</Label>
                            <Input id="school_student_id" type="text" v-model="form.school_student_id" placeholder="If already assigned" class="h-9 px-2.5 py-1.5" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="lrn">Learner Reference Number (LRN)</Label>
                            <Input id="lrn" type="text" v-model="form.lrn" placeholder="12 digits" class="h-9 px-2.5 py-1.5" maxlength="12" />
                            <InputError :message="errors.lrn" />
                        </div>
                    </div>
                </div>

                <!-- Name -->
                <div class="grid gap-3">
                    <p class="text-sm font-medium text-muted-foreground">Personal information</p>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="last_name">Last Name</Label>
                            <Input id="last_name" type="text" required v-model="form.last_name" autocomplete="family-name" class="h-9 px-2.5 py-1.5" />
                            <InputError :message="errors.last_name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="first_name">First Name</Label>
                            <Input id="first_name" type="text" required v-model="form.first_name" autocomplete="given-name" class="h-9 px-2.5 py-1.5" />
                            <InputError :message="errors.first_name" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="grid gap-2">
                            <Label for="middle_name">Complete Middle Name</Label>
                            <Input id="middle_name" type="text" v-model="form.middle_name" class="h-9 px-2.5 py-1.5" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="extension_name">Extension</Label>
                            <Input id="extension_name" type="text" v-model="form.extension_name" placeholder="Jr., Sr., III" class="h-9 px-2.5 py-1.5" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="nickname">Nickname</Label>
                            <Input id="nickname" type="text" v-model="form.nickname" class="h-9 px-2.5 py-1.5" />
                        </div>
                    </div>
                </div>

                <!-- Birth & gender -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="grid gap-2">
                        <Label for="birth_date">Birthdate</Label>
                        <Input id="birth_date" type="date" required v-model="form.birth_date" class="h-9 px-2.5 py-1.5" />
                        <InputError :message="errors.birth_date" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Age</Label>
                        <div class="flex h-9 items-center gap-2 rounded-md border border-border/60 bg-muted/40 px-2.5 text-sm text-muted-foreground">
                            <CalendarDays class="size-4" />
                            {{ age ?? '—' }}
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="gender">Gender</Label>
                        <Select v-model="form.gender">
                            <SelectTrigger id="gender" class="h-9">
                                <SelectValue placeholder="Select gender" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="gender in genders" :key="gender.value" :value="gender.value">{{ gender.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.gender" />
                    </div>
                </div>

                <!-- Contact -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="religion">Religion</Label>
                        <Input id="religion" type="text" v-model="form.religion" class="h-9 px-2.5 py-1.5" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="nationality">Nationality</Label>
                        <Input id="nationality" type="text" v-model="form.nationality" placeholder="e.g. Filipino" class="h-9 px-2.5 py-1.5" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="citizenship">Citizenship</Label>
                        <Input id="citizenship" type="text" v-model="form.citizenship" placeholder="e.g. Filipino" class="h-9 px-2.5 py-1.5" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="mobile_number">Student Contact Number</Label>
                        <Input id="mobile_number" type="tel" v-model="form.mobile_number" placeholder="+63 900 000 0000" class="h-9 px-2.5 py-1.5" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Student's Email Address</Label>
                        <Input id="email" type="email" v-model="form.email" placeholder="student@example.com" class="h-9 px-2.5 py-1.5" />
                        <InputError :message="errors.email" />
                    </div>
                </div>

                <!-- Photo -->
                <div class="grid gap-2">
                    <Label>2x2 Picture</Label>
                    <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPhotoSelected" />
                    <div class="flex items-start gap-4">
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border/60 bg-muted/40">
                            <img v-if="photoPreview || photoUrl" :src="(photoPreview || photoUrl) ?? undefined" alt="2x2 photo preview" class="h-full w-full object-cover" />
                            <ImagePlus v-else class="size-8 text-muted-foreground/50" />
                        </div>
                        <div class="grid gap-2">
                            <Button type="button" variant="outline" size="sm" class="h-9 gap-2" @click="fileInput?.click()">
                                <Upload class="size-4" />
                                {{ photoUrl || photoPreview ? 'Replace photo' : 'Attach photo' }}
                            </Button>
                            <button v-if="photoFile || photoPreview" type="button" class="flex items-center gap-1 text-xs text-muted-foreground hover:text-destructive" @click="clearPhoto">
                                <X class="size-3.5" />
                                Remove
                            </button>
                            <p class="text-xs leading-5 text-muted-foreground">Please attach a 2x2 picture with white background.</p>
                        </div>
                    </div>
                </div>

                <!-- Interests -->
                <div class="grid gap-3">
                    <p class="text-sm font-medium text-muted-foreground">Interests</p>
                    <div class="grid grid-cols-2 gap-2">
                        <label
                            v-for="interest in interests"
                            :key="interest.value"
                            class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-border/60 px-3 py-2.5 text-sm transition-colors hover:border-primary/30"
                            :class="form.interests.includes(interest.value) ? 'border-primary/40 bg-primary/5' : ''"
                        >
                            <input v-model="form.interests" type="checkbox" :value="interest.value" class="size-4 rounded border-border text-primary" />
                            {{ interest.label }}
                        </label>
                    </div>
                </div>

                <!-- Background -->
                <div class="grid gap-3">
                    <p class="text-sm font-medium text-muted-foreground">Background</p>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="place_of_birth">Place of Birth</Label>
                            <Input id="place_of_birth" type="text" v-model="form.place_of_birth" class="h-9 px-2.5 py-1.5" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="ethnicity">Ethnicity</Label>
                            <Input id="ethnicity" type="text" v-model="form.ethnicity" class="h-9 px-2.5 py-1.5" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="mother_tongue">Mother Tongue</Label>
                            <Input id="mother_tongue" type="text" v-model="form.mother_tongue" placeholder="e.g. Ilocano, Tagalog" class="h-9 px-2.5 py-1.5" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="telephone_number">Landline</Label>
                            <Input id="telephone_number" type="tel" v-model="form.telephone_number" class="h-9 px-2.5 py-1.5" />
                        </div>
                    </div>

                    <label class="flex w-fit cursor-pointer items-center gap-2.5 text-sm font-medium">
                        <input v-model="form.is_indigenous" type="checkbox" class="size-4 rounded border-border text-primary" />
                        Part of an Indigenous Tribe
                    </label>
                </div>

                <!-- Present address -->
                <div class="grid gap-3">
                    <p class="text-sm font-medium text-muted-foreground">Present Address</p>

                    <div class="grid gap-2">
                        <Label for="current_address">House No. and Street Name</Label>
                        <Input id="current_address" type="text" v-model="form.current_address" placeholder="Present address (house no. and street name)" class="h-9 px-2.5 py-1.5" />
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="grid gap-2">
                            <Label for="current_province">Province</Label>
                            <Combobox id="current_province" v-model="form.current_province" :options="provinceOptions" placeholder="Begin typing for results…" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="current_city">City</Label>
                            <Combobox
                                id="current_city"
                                v-model="form.current_city"
                                :options="cityOptions"
                                placeholder="Begin typing for results…"
                                :disabled="!form.current_province"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="current_barangay">Barangay</Label>
                            <Input id="current_barangay" type="text" v-model="form.current_barangay" placeholder="Begin typing for results…" class="h-9 px-2.5 py-1.5" />
                        </div>
                    </div>
                </div>

                <Button type="submit" class="h-10" :disabled="processing">
                    <LoaderCircle v-if="processing || photoUploading" class="size-4 animate-spin" />
                    {{ processing || photoUploading ? 'Saving…' : 'Save and continue' }}
                </Button>
            </CardContent>
        </form>
    </Card>
</template>