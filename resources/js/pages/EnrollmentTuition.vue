<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import api, { extractError } from '@/lib/api';
import { BadgeCheck, LoaderCircle, Wallet } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Application {
    id: number;
    reference_number: string;
}

const TUITION_PLAN = 'School Tuition Plan';

const props = withDefaults(defineProps<{
    application: Application;
    initialTuitionPlan?: string | null;
    apiBase?: string;
}>(), {
    apiBase: '/public/enrollments',
});

const emit = defineEmits<{ submitted: [data: { tuition_plan: string }] }>();

const plan = ref(TUITION_PLAN);
const processing = ref(false);

const submit = async () => {
    processing.value = true;

    try {
        await api.put<{ data: { tuition_plan: string } }>(`${props.apiBase}/${props.application.id}/details`, {
            tuition_plan: plan.value,
        });
        toast.success('School fees plan saved.');
        emit('submitted', { tuition_plan: plan.value });
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    if (props.initialTuitionPlan) {
        plan.value = props.initialTuitionPlan;
    }
});
</script>

<template>
    <Card class="relative overflow-hidden border-border/60 bg-card/60">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

        <form @submit.prevent="submit">
            <CardHeader>
                <CardTitle>School fees plan</CardTitle>
                <CardDescription>Choose the tuition plan you intend to avail for the incoming school year.</CardDescription>
            </CardHeader>

            <CardContent class="grid gap-6">
                <div class="grid gap-2">
                    <label class="flex cursor-not-allowed items-start gap-3 rounded-xl border border-primary/25 bg-primary/5 p-4 opacity-90">
                        <input v-model="plan" type="radio" name="tuition_plan" :value="TUITION_PLAN" class="mt-1 accent-[hsl(26_57%_40%)]" disabled />
                        <span class="grid gap-1">
                            <span class="flex items-center gap-2 text-sm font-medium text-foreground">
                                <Wallet class="size-4 text-primary" />
                                Tuition Plan
                            </span>
                            <span class="flex items-center gap-1.5 text-sm text-muted-foreground">
                                <BadgeCheck class="size-4 text-primary" />
                                {{ TUITION_PLAN }}
                            </span>
                        </span>
                    </label>
                </div>

                <Button type="submit" class="h-10" :disabled="processing">
                    <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                    {{ processing ? 'Saving…' : 'Continue to medical history' }}
                </Button>
            </CardContent>
        </form>
    </Card>
</template>