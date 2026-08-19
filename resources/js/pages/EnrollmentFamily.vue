<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Application {
    id: number;
    reference_number: string;
}

interface ParentForm {
    not_applicable: boolean;
    last_name: string;
    first_name: string;
    middle_name: string;
    maiden_name: string;
    mobile_number: string;
    email: string;
    occupation: string;
    address: string;
}

interface GuardianForm {
    last_name: string;
    first_name: string;
    middle_name: string;
    mobile_number: string;
    relationship: string;
    address: string;
    occupation: string;
}

interface FamilyForm {
    family_monthly_income: string;
    emergency_contact_type: string;
    emergency_contact_name: string;
    emergency_contact_mobile: string;
    father: ParentForm;
    mother: ParentForm;
    guardian: GuardianForm;
}

interface InitialFamily {
    father: Record<string, unknown> | null;
    mother: Record<string, unknown> | null;
    guardian: Record<string, unknown> | null;
    family_monthly_income: string | null;
    emergency_contact_type?: string | null;
    emergency_contact_name?: string | null;
    emergency_contact_mobile?: string | null;
}

const props = withDefaults(defineProps<{
    application: Application;
    initialFamily?: Record<string, unknown> | null;
    apiBase?: string;
}>(), {
    apiBase: '/public/enrollments',
});

const emit = defineEmits<{ submitted: [data: { family: Record<string, unknown> }] }>();

const form = ref<FamilyForm>({
    family_monthly_income: '',
    emergency_contact_type: 'parent',
    emergency_contact_name: '',
    emergency_contact_mobile: '',
    father: {
        not_applicable: false,
        last_name: '',
        first_name: '',
        middle_name: '',
        maiden_name: '',
        mobile_number: '',
        email: '',
        occupation: '',
        address: '',
    },
    mother: {
        not_applicable: false,
        last_name: '',
        first_name: '',
        middle_name: '',
        maiden_name: '',
        mobile_number: '',
        email: '',
        occupation: '',
        address: '',
    },
    guardian: {
        last_name: '',
        first_name: '',
        middle_name: '',
        mobile_number: '',
        relationship: '',
        address: '',
        occupation: '',
    },
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

onMounted(() => {
    const family = props.initialFamily as InitialFamily | null;

    if (!family) {
        return;
    }

    const parentToForm = (parent: Record<string, unknown> | null) => {
        const value = <T>(key: string, fallback: T): T => (parent && parent[key] !== null && parent[key] !== undefined ? (parent[key] as T) : fallback);

        return {
            not_applicable: Boolean(value('not_applicable', false)),
            last_name: value('last_name', '') ?? '',
            first_name: value('first_name', '') ?? '',
            middle_name: value('middle_name', '') ?? '',
            maiden_name: value('maiden_name', '') ?? '',
            mobile_number: value('mobile_number', '') ?? '',
            email: value('email', '') ?? '',
            occupation: value('occupation', '') ?? '',
            address: value('address', '') ?? '',
        };
    };

    form.value.family_monthly_income = family.family_monthly_income ?? '';
    form.value.emergency_contact_type = String(family.emergency_contact_type ?? 'parent');
    form.value.emergency_contact_name = String(family.emergency_contact_name ?? '');
    form.value.emergency_contact_mobile = String(family.emergency_contact_mobile ?? '');
    form.value.father = parentToForm(family.father);
    form.value.mother = parentToForm(family.mother);

    if (family.guardian) {
        const guardian = family.guardian;
        const value = <T>(key: string, fallback: T): T => (guardian[key] !== null && guardian[key] !== undefined ? (guardian[key] as T) : fallback);

        form.value.guardian = {
            last_name: value('last_name', '') ?? '',
            first_name: value('first_name', '') ?? '',
            middle_name: value('middle_name', '') ?? '',
            mobile_number: value('mobile_number', '') ?? '',
            relationship: value('relationship', '') ?? '',
            address: value('address', '') ?? '',
            occupation: value('occupation', '') ?? '',
        };
    }
});

const isParentFilled = (parent: ParentForm): boolean => {
    return Object.entries(parent)
        .filter(([key]) => key !== 'not_applicable' && key !== 'maiden_name')
        .some(([, value]) => typeof value === 'string' && value.trim() !== '');
};

const buildParentPayload = (parent: ParentForm) => {
    if (!parent.not_applicable && !isParentFilled(parent)) {
        return null;
    }

    return {
        not_applicable: parent.not_applicable,
        last_name: parent.last_name.trim() || null,
        first_name: parent.first_name.trim() || null,
        middle_name: parent.middle_name.trim() || null,
        maiden_name: parent.maiden_name.trim() || null,
        mobile_number: parent.mobile_number.trim() || null,
        email: parent.email.trim() || null,
        occupation: parent.occupation.trim() || null,
        address: parent.address.trim() || null,
    };
};

const validate = (): Record<string, string> => {
    const nextErrors: Record<string, string> = {};
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (form.value.father.email && !emailPattern.test(form.value.father.email)) nextErrors['father.email'] = 'Enter a valid email address.';
    if (form.value.mother.email && !emailPattern.test(form.value.mother.email)) nextErrors['mother.email'] = 'Enter a valid email address.';

    return nextErrors;
};

const submit = async () => {
    errors.value = validate();

    if (Object.keys(errors.value).length) {
        return;
    }

    processing.value = true;

    const payload: Record<string, unknown> = {
        family_monthly_income: form.value.family_monthly_income.trim() || null,
        emergency_contact_type: form.value.emergency_contact_type,
        emergency_contact_name: form.value.emergency_contact_name.trim() || null,
        emergency_contact_mobile: form.value.emergency_contact_mobile.trim() || null,
    };

    const father = buildParentPayload(form.value.father);
    const mother = buildParentPayload(form.value.mother);

    if (father) payload.father = father;
    if (mother) payload.mother = mother;

    const guardian = form.value.guardian;
    if (Object.values(guardian).some((value) => typeof value === 'string' && value.trim() !== '')) {
        payload.guardian = {
            last_name: guardian.last_name.trim() || null,
            first_name: guardian.first_name.trim() || null,
            middle_name: guardian.middle_name.trim() || null,
            mobile_number: guardian.mobile_number.trim() || null,
            relationship: guardian.relationship.trim() || null,
            address: guardian.address.trim() || null,
            occupation: guardian.occupation.trim() || null,
        };
    }

    try {
        const response = await api.put<{ data: { family: Record<string, unknown> } }>(`${props.apiBase}/${props.application.id}/family`, payload);
        toast.success('Family background saved.');
        emit('submitted', { family: response.data.data.family });
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(
            Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']),
        );
    } finally {
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
                        <CardTitle>Contact information</CardTitle>
                        <CardDescription>Emergency contact, parents, and guardian details.</CardDescription>
                    </div>
                    <span class="shrink-0 rounded-full bg-primary/10 px-3 py-1 font-mono text-[11px] font-medium text-primary ring-1 ring-primary/15">
                        {{ application.reference_number }}
                    </span>
                </div>
            </CardHeader>

            <CardContent class="grid gap-6">
                <section class="grid gap-3 rounded-xl border border-border/60 p-4">
                    <p class="text-sm font-medium text-muted-foreground">Emergency contact</p>
                    <div class="grid grid-cols-3 gap-2">
                        <label
                            v-for="option in [
                                { value: 'parent', label: 'Parent' },
                                { value: 'guardian', label: 'Guardian' },
                                { value: 'others', label: 'Others' },
                            ]"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/60 px-3 py-2.5 text-sm"
                            :class="form.emergency_contact_type === option.value ? 'border-primary/40 bg-primary/5' : ''"
                        >
                            <input v-model="form.emergency_contact_type" type="radio" name="emergency_contact_type" :value="option.value" class="size-4 accent-[hsl(26_57%_40%)]" />
                            {{ option.label }}
                        </label>
                    </div>
                    <div v-if="form.emergency_contact_type === 'others'" class="grid grid-cols-2 gap-3">
                        <div class="grid gap-2">
                            <Label for="emergency-name">Emergency contact person</Label>
                            <Input id="emergency-name" v-model="form.emergency_contact_name" class="h-9 px-2.5 py-1.5" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="emergency-mobile">Primary contact number</Label>
                            <Input id="emergency-mobile" v-model="form.emergency_contact_mobile" type="tel" class="h-9 px-2.5 py-1.5" />
                        </div>
                    </div>
                </section>

                <!-- Father -->
                <section class="grid gap-3 rounded-xl border border-border/60 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm font-medium text-muted-foreground">Father's information</p>
                        <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-muted-foreground">
                            <input v-model="form.father.not_applicable" type="checkbox" class="size-4 rounded border-border text-primary" />
                            Check if not applicable
                        </label>
                    </div>

                    <div :class="form.father.not_applicable ? 'pointer-events-none opacity-40' : ''" class="grid gap-3">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="grid gap-2">
                                <Label for="father-last-name">Last Name</Label>
                                <Input id="father-last-name" type="text" v-model="form.father.last_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="father-first-name">First Name</Label>
                                <Input id="father-first-name" type="text" v-model="form.father.first_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="father-middle-name">Middle Name</Label>
                                <Input id="father-middle-name" type="text" v-model="form.father.middle_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="father-phone">Phone Number</Label>
                                <Input id="father-phone" type="tel" v-model="form.father.mobile_number" placeholder="+63 900 000 0000" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="father-email">Email</Label>
                                <Input id="father-email" type="email" v-model="form.father.email" placeholder="Write NONE if unavailable" class="h-9 px-2.5 py-1.5" />
                                <InputError :message="errors['father.email']" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="father-occupation">Occupation</Label>
                                <Input id="father-occupation" type="text" v-model="form.father.occupation" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="father-address">Office Address</Label>
                                <Input id="father-address" type="text" v-model="form.father.address" class="h-9 px-2.5 py-1.5" />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Mother -->
                <section class="grid gap-3 rounded-xl border border-border/60 p-4">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm font-medium text-muted-foreground">Mother</p>
                        <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-muted-foreground">
                            <input v-model="form.mother.not_applicable" type="checkbox" class="size-4 rounded border-border text-primary" />
                            Check if not applicable
                        </label>
                    </div>

                    <div :class="form.mother.not_applicable ? 'pointer-events-none opacity-40' : ''" class="grid gap-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="mother-last-name">Last Name</Label>
                                <Input id="mother-last-name" type="text" v-model="form.mother.last_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="mother-first-name">First Name</Label>
                                <Input id="mother-first-name" type="text" v-model="form.mother.first_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="mother-middle-name">Middle Name</Label>
                                <Input id="mother-middle-name" type="text" v-model="form.mother.middle_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="mother-maiden-name">Mother's Full Name When She's Single</Label>
                                <Input id="mother-maiden-name" type="text" v-model="form.mother.maiden_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="mother-phone">Phone Number</Label>
                                <Input id="mother-phone" type="tel" v-model="form.mother.mobile_number" placeholder="+63 900 000 0000" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="mother-email">Email</Label>
                                <Input id="mother-email" type="email" v-model="form.mother.email" placeholder="Write NONE if unavailable" class="h-9 px-2.5 py-1.5" />
                                <InputError :message="errors['mother.email']" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="mother-occupation">Occupation</Label>
                                <Input id="mother-occupation" type="text" v-model="form.mother.occupation" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="mother-address">Office Address</Label>
                                <Input id="mother-address" type="text" v-model="form.mother.address" class="h-9 px-2.5 py-1.5" />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="family_monthly_income">Family's TOTAL Monthly Income</Label>
                            <Input id="family_monthly_income" type="text" v-model="form.family_monthly_income" placeholder="e.g. PHP 20,000 - 30,000" class="h-9 px-2.5 py-1.5" />
                        </div>
                    </div>
                </section>

                <!-- Guardian -->
                <section class="grid gap-3 rounded-xl border border-border/60 p-4">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Guardian information</p>
                        <p class="text-xs leading-5 text-muted-foreground">Guardian's name and primary contact number</p>
                    </div>

                    <div class="grid gap-3">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="grid gap-2">
                                <Label for="guardian-last-name">Last Name</Label>
                                <Input id="guardian-last-name" type="text" v-model="form.guardian.last_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="guardian-first-name">First Name</Label>
                                <Input id="guardian-first-name" type="text" v-model="form.guardian.first_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="guardian-middle-name">Middle Name</Label>
                                <Input id="guardian-middle-name" type="text" v-model="form.guardian.middle_name" class="h-9 px-2.5 py-1.5" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="guardian-phone">Phone Number</Label>
                                <Input id="guardian-phone" type="tel" v-model="form.guardian.mobile_number" placeholder="+63 900 000 0000" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="guardian-relationship">Relationship</Label>
                                <Input id="guardian-relationship" type="text" v-model="form.guardian.relationship" placeholder="e.g. Aunt, Grandmother" class="h-9 px-2.5 py-1.5" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="grid gap-2">
                                <Label for="guardian-address">Guardian's Address</Label>
                                <Input id="guardian-address" type="text" v-model="form.guardian.address" class="h-9 px-2.5 py-1.5" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="guardian-occupation">Occupation</Label>
                                <Input id="guardian-occupation" type="text" v-model="form.guardian.occupation" class="h-9 px-2.5 py-1.5" />
                            </div>
                        </div>
                    </div>
                </section>

                <Button type="submit" class="h-10" :disabled="processing">
                    <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                    {{ processing ? 'Saving…' : 'Submit family background' }}
                </Button>
            </CardContent>
        </form>
    </Card>
</template>