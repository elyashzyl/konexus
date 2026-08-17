<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { HeartPulse, LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Application {
    id: number;
    reference_number: string;
}

const FAMILY_HISTORY_OPTIONS = [
    { value: 'asthma', label: 'Asthma' },
    { value: 'diabetes', label: 'Diabetes' },
    { value: 'hypertension', label: 'Hypertension' },
    { value: 'heart-disease', label: 'Heart Disease' },
    { value: 'cancer', label: 'Cancer' },
    { value: 'tuberculosis', label: 'Tuberculosis' },
    { value: 'epilepsy-seizures', label: 'Epilepsy / Seizures' },
    { value: 'kidney-disease', label: 'Kidney Disease' },
    { value: 'mental-health', label: 'Mental Health' },
];

const HOSPITAL_OPTIONS = [
    { value: 'notre-dame-de-chartres', label: 'Notre Dame de Chartres Hospital' },
    { value: 'baguio-general', label: 'Baguio General Hospital' },
    { value: 'slu-sacred-heart', label: 'SLU Sacred Heart' },
    { value: 'pines', label: 'Pines' },
    { value: 'nearest-hospital', label: 'Nearest Hospital' },
];

const props = withDefaults(defineProps<{
    application: Application;
    initialMedical?: Record<string, unknown> | null;
    apiBase?: string;
}>(), {
    apiBase: '/public/enrollments',
});

const emit = defineEmits<{ submitted: [data: { medical_history: Record<string, unknown> }] }>();

const allergies = ref('');
const familyHistory = ref<string[]>([]);
const familyHistoryOthers = ref('');
const emergencyHospital = ref('');
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const toggleHistory = (value: string) => {
    const index = familyHistory.value.indexOf(value);

    if (index === -1) {
        familyHistory.value.push(value);
    } else {
        familyHistory.value.splice(index, 1);
    }
};

const validate = (): Record<string, string> => {
    const nextErrors: Record<string, string> = {};

    if (!emergencyHospital.value) nextErrors.emergency_hospital = 'Please choose a hospital.';

    return nextErrors;
};

const submit = async () => {
    errors.value = validate();

    if (Object.keys(errors.value).length) {
        return;
    }

    processing.value = true;

    const payload = {
        allergies: allergies.value.trim(),
        family_history: familyHistory.value,
        family_history_others: familyHistoryOthers.value.trim() || null,
        emergency_hospital: emergencyHospital.value,
    };

    try {
        await api.put<{ data: { medical_history: Record<string, unknown> } }>(
            `${props.apiBase}/${props.application.id}/details`,
            { medical_history: payload },
        );
        toast.success('Medical history saved.');
        emit('submitted', { medical_history: payload });
    } catch (error) {
        toast.error(extractError(error));
        errors.value = Object.fromEntries(
            Object.entries(extractFieldErrors(error)).map(([key, messages]) => [key, messages[0] ?? '']),
        );
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    const medical = props.initialMedical;

    if (!medical) {
        return;
    }

    allergies.value = String(medical.allergies ?? '');
    familyHistory.value = Array.isArray(medical.family_history) ? medical.family_history.map(String) : [];
    familyHistoryOthers.value = String(medical.family_history_others ?? '');
    emergencyHospital.value = String(medical.emergency_hospital ?? '');
});
</script>

<template>
    <Card class="relative overflow-hidden border-border/60 bg-card/60">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

        <form @submit.prevent="submit">
            <CardHeader>
                <CardTitle>Medical history</CardTitle>
                <CardDescription>
                    Please provide any relevant medical information to help us care for your child while in school.
                </CardDescription>
            </CardHeader>

            <CardContent class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="allergies">Allergies</Label>
                    <Textarea
                        id="allergies"
                        v-model="allergies"
                        rows="3"
                        placeholder="Please leave blank if the child has no known allergies"
                        class="resize-none"
                    />
                </div>

                <div class="grid gap-3">
                    <p class="text-sm font-medium text-muted-foreground">Does your family have a history of</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <label
                            v-for="option in FAMILY_HISTORY_OPTIONS"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/60 bg-background/60 px-3 py-2 text-sm"
                            :class="familyHistory.includes(option.value) ? 'border-primary/40 bg-primary/5' : ''"
                        >
                            <input
                                type="checkbox"
                                :checked="familyHistory.includes(option.value)"
                                class="size-4 accent-[hsl(26_57%_40%)]"
                                @change="toggleHistory(option.value)"
                            />
                            {{ option.label }}
                        </label>
                    </div>
                    <div class="grid gap-2">
                        <Label for="family_history_others">Others</Label>
                        <Input id="family_history_others" v-model="familyHistoryOthers" type="text" class="h-9 px-2.5 py-1.5" />
                    </div>
                </div>

                <div class="grid gap-3">
                    <p class="text-sm font-medium text-muted-foreground">
                        In case of an emergency, which hospital would you like your child to be brought to?
                    </p>
                    <div class="grid gap-2">
                        <label
                            v-for="option in HOSPITAL_OPTIONS"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-border/60 bg-background/60 px-3 py-2.5 text-sm"
                            :class="emergencyHospital === option.value ? 'border-primary/40 bg-primary/5' : ''"
                        >
                            <input
                                v-model="emergencyHospital"
                                type="radio"
                                name="emergency_hospital"
                                :value="option.value"
                                class="size-4 accent-[hsl(26_57%_40%)]"
                            />
                            <span class="flex items-center gap-2">
                                <HeartPulse class="size-4 text-muted-foreground" />
                                {{ option.label }}
                            </span>
                        </label>
                    </div>
                    <InputError :message="errors.emergency_hospital" />
                </div>

                <Button type="submit" class="h-10" :disabled="processing">
                    <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                    {{ processing ? 'Saving…' : 'Continue' }}
                </Button>
            </CardContent>
        </form>
    </Card>
</template>