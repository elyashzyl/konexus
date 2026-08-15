<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { CalendarDays, Camera, FileCheck2, LoaderCircle, ScrollText } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Application {
    id: number;
    reference_number: string;
}

const props = defineProps<{
    application: Application;
    initialAgreement?: Record<string, unknown> | null;
}>();

const emit = defineEmits<{ submitted: [data: { agreement: Record<string, unknown> }] }>();

const photoConsent = ref<boolean | null>(null);
const registrationConsent = ref(false);
const credentialingConsent = ref(false);
const rulesConsent = ref(false);
const errors = ref<Record<string, string>>({});
const processing = ref(false);

const today = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: '2-digit', day: '2-digit' });
const todayIso = new Date().toISOString().slice(0, 10);
const initialPayment = '10,000';

const validate = (): Record<string, string> => {
    const nextErrors: Record<string, string> = {};

    if (photoConsent.value === null) nextErrors.photo_consent = 'Please choose an option.';
    if (!registrationConsent.value) nextErrors.registration_consent = 'Please agree to the certificate of registration.';
    if (!credentialingConsent.value) nextErrors.credentialing_consent = 'Please agree to the admission credentialing.';
    if (!rulesConsent.value) nextErrors.rules_consent = 'Please agree to the rules concerning fees.';

    return nextErrors;
};

const submit = async () => {
    errors.value = validate();

    if (Object.keys(errors.value).length) {
        return;
    }

    processing.value = true;

    const payload = {
        photo_consent: photoConsent.value,
        registration_consent: registrationConsent.value,
        credentialing_consent: credentialingConsent.value,
        rules_consent: rulesConsent.value,
        date_of_registration: todayIso,
        initial_payment: 10000,
    };

    try {
        await api.put<{ data: Record<string, unknown> }>(`/public/enrollments/${props.application.id}/details`, { agreement: payload });
        toast.success('School agreement saved.');
        emit('submitted', { agreement: payload });
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
    const agreement = props.initialAgreement;

    if (!agreement) {
        return;
    }

    photoConsent.value = agreement.photo_consent === true || agreement.photo_consent === 1 || agreement.photo_consent === '1';
    registrationConsent.value = agreement.registration_consent === true || agreement.registration_consent === 1;
    credentialingConsent.value = agreement.credentialing_consent === true || agreement.credentialing_consent === 1;
    rulesConsent.value = agreement.rules_consent === true || agreement.rules_consent === 1;
});
</script>

<template>
    <Card class="relative overflow-hidden border-border/60 bg-card/60">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

        <form @submit.prevent="submit">
            <CardHeader>
                <CardTitle>School agreement</CardTitle>
                <CardDescription>Please review and agree to the following before completing your application.</CardDescription>
            </CardHeader>

            <CardContent class="grid gap-6">
                <div class="grid gap-3">
                    <p class="flex items-center gap-2 text-sm font-medium text-foreground">
                        <Camera class="size-4 text-primary" />
                        Photos of students
                    </p>
                    <p class="text-sm leading-6 text-muted-foreground">
                        As a school, we often take photos of students during school events and activities to share on our website, social
                        media, and other online platforms. We understand that some parents may not want their child/ren's photo to be
                        posted online, and we respect your privacy. If you do not want your child's photo to be posted online, please let
                        us know by ticking the appropriate box below.
                    </p>
                    <div class="grid gap-2">
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-border/60 bg-background/60 px-3 py-2.5 text-sm"
                            :class="photoConsent === true ? 'border-primary/40 bg-primary/5' : ''"
                        >
                            <input v-model="photoConsent" type="radio" name="photo_consent" :value="true" class="mt-0.5 size-4 accent-[hsl(26_57%_40%)]" />
                            <span>
                                Yes, I give permission for my child's photo to be shared online on the school's website, social media, and
                                other online platforms.
                            </span>
                        </label>
                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-border/60 bg-background/60 px-3 py-2.5 text-sm"
                            :class="photoConsent === false ? 'border-primary/40 bg-primary/5' : ''"
                        >
                            <input v-model="photoConsent" type="radio" name="photo_consent" :value="false" class="mt-0.5 size-4 accent-[hsl(26_57%_40%)]" />
                            <span>No, I do not give permission for my child's photo to be shared online.</span>
                        </label>
                    </div>
                    <InputError :message="errors.photo_consent" />
                </div>

                <div class="grid gap-3">
                    <p class="flex items-center gap-2 text-sm font-medium text-foreground">
                        <FileCheck2 class="size-4 text-primary" />
                        Certificate of Registration
                    </p>
                    <p class="text-sm leading-6 text-muted-foreground">
                        I hereby certify that the above information given is true and correct to the best of my knowledge and I allow the
                        Department of Education / Baguio Patriotic High School (BPHS) to use my child's details to create and/or update
                        his/her learner profile in the Learner Information System. The information herein shall be treated as confidential
                        in compliance with the Data Privacy Act of 2012*.
                    </p>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border/60 bg-background/60 px-3 py-2.5 text-sm" :class="registrationConsent ? 'border-primary/40 bg-primary/5' : ''">
                        <input v-model="registrationConsent" type="checkbox" class="mt-0.5 size-4 accent-[hsl(26_57%_40%)]" />
                        <span>I agree to the certificate of registration above.</span>
                    </label>
                    <InputError :message="errors.registration_consent" />
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border/60 bg-background/60 px-3 py-2.5 text-sm" :class="credentialingConsent ? 'border-primary/40 bg-primary/5' : ''">
                        <input v-model="credentialingConsent" type="checkbox" class="mt-0.5 size-4 accent-[hsl(26_57%_40%)]" />
                        <span>I agree that my pre-enrollment is subject to admission credentialing.</span>
                    </label>
                    <InputError :message="errors.credentialing_consent" />
                </div>

                <div class="grid gap-3">
                    <p class="flex items-center gap-2 text-sm font-medium text-foreground">
                        <ScrollText class="size-4 text-primary" />
                        Rules concerning fees
                    </p>
                    <div class="grid gap-3 rounded-lg border border-border/60 bg-background/60 p-4 text-[13px] leading-6 text-muted-foreground">
                        <p>Private schools are authorized to drop pupils and students for non-payment of tuition and other fees under DepEd rules and regulations.</p>
                        <p>
                            "A student who withdraws, in writing, within the first week after the beginning of classes may be charged ten
                            percent of the total amount due or twenty percent if within the second week of classes, regardless of whether
                            or not he has attended classes. The student may be charged all the school fees in full if he withdraws anytime
                            after the second week of classes." (Underscoring supplied). Sec. 66, Art. XIII, Manual of Regulations for Private Schools.
                        </p>
                        <p>
                            "The release of the transfer credentials of any pupil or student may be withheld for reasons of suspension,
                            expulsion, or non-payment of financial obligations or property responsibility of the pupil or student to the
                            school. The credentials shall be released as soon as his obligation shall have been settled or the penalty of
                            suspension or expulsion lifted." Sec. 72, Art. XIII, Manual of Regulations for Private Schools.
                        </p>
                        <p>Students with no ID card will not be admitted to class. ID card must be returned upon graduation or when the student leaves BPHS. Loss of ID or non-return of ID card will incur a fee of Php 500.</p>
                        <p>For installment payments: Late payment will have 10% surcharge.</p>
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-border/60 bg-background/60 px-3 py-2.5 text-sm" :class="rulesConsent ? 'border-primary/40 bg-primary/5' : ''">
                        <input v-model="rulesConsent" type="checkbox" class="mt-0.5 size-4 accent-[hsl(26_57%_40%)]" />
                        <span>We hereby bind ourselves to abide by DepEd and BPHS rules and regulations.</span>
                    </label>
                    <InputError :message="errors.rules_consent" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label>Date of registration</Label>
                        <div class="flex h-9 items-center gap-2 rounded-md border border-border/60 bg-muted/40 px-2.5 text-sm text-muted-foreground">
                            <CalendarDays class="size-4" />
                            {{ today }}
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label>Initial payment</Label>
                        <div class="flex h-9 items-center rounded-md border border-border/60 bg-muted/40 px-2.5 text-sm font-medium text-foreground">
                            Php {{ initialPayment }}
                        </div>
                    </div>
                </div>

                <Button type="submit" class="h-10" :disabled="processing">
                    <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                    {{ processing ? 'Saving…' : 'Continue to signature' }}
                </Button>
            </CardContent>
        </form>
    </Card>
</template>