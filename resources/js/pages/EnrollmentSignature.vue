<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import SignaturePad from '@/components/ui/SignaturePad.vue';
import api, { extractError } from '@/lib/api';
import { LoaderCircle, PenLine } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Application {
    id: number;
    reference_number: string;
}

interface SignatureRecord {
    role: string;
    signer_name: string;
    signature_data: string;
}

const props = defineProps<{
    application: Application;
    studentName: string;
    parentName: string;
    initialSignatures?: SignatureRecord[] | null;
}>();

const emit = defineEmits<{ submitted: [] }>();

const studentSignature = ref('');
const parentSignature = ref('');
const processing = ref(false);

const missing = computed(() => {
    const errors: string[] = [];

    if (!studentSignature.value) errors.push('student_signature');
    if (!parentSignature.value) errors.push('parent_signature');

    return errors;
});

const submit = async () => {
    if (missing.value.length) {
        toast.error('Please sign inside both boxes before continuing.');
        return;
    }

    processing.value = true;

    try {
        await api.post(`/public/enrollments/${props.application.id}/signature`, {
            role: 'student',
            signer_name: props.studentName.trim() || 'Student',
            signature_data: studentSignature.value,
        });
        await api.post(`/public/enrollments/${props.application.id}/signature`, {
            role: 'parent',
            signer_name: props.parentName.trim() || 'Parent/Guardian',
            signature_data: parentSignature.value,
        });
        toast.success('Signatures captured.');
        emit('submitted');
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    const signatures = props.initialSignatures;

    if (!signatures?.length) {
        return;
    }

    studentSignature.value = signatures.find((signature) => signature.role === 'student')?.signature_data ?? '';
    parentSignature.value = signatures.find((signature) => signature.role === 'parent')?.signature_data ?? '';
});
</script>

<template>
    <Card class="relative overflow-hidden border-border/60 bg-card/60">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

        <form @submit.prevent="submit">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <PenLine class="size-5 text-primary" />
                    Student / Parent signature
                </CardTitle>
                <CardDescription>Please sign inside the box, using your mouse, stylus, or any pointing device.</CardDescription>
            </CardHeader>

            <CardContent class="grid gap-8">
                <div class="grid gap-2">
                    <p class="text-sm font-medium text-foreground">Student's signature</p>
                    <SignaturePad v-model="studentSignature" />
                    <p v-if="missing.includes('student_signature')" class="text-xs text-destructive">Please sign before continuing.</p>
                </div>

                <div class="grid gap-2">
                    <p class="text-sm font-medium text-foreground">Parent / Guardian signature</p>
                    <SignaturePad v-model="parentSignature" />
                    <p v-if="missing.includes('parent_signature')" class="text-xs text-destructive">Please sign before continuing.</p>
                </div>

                <Button type="submit" class="h-10" :disabled="processing">
                    <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                    {{ processing ? 'Submitting…' : 'Submit application' }}
                </Button>
            </CardContent>
        </form>
    </Card>
</template>