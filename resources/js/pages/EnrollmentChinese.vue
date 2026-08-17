<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Combobox } from '@/components/ui/combobox';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import api, { extractError, extractFieldErrors } from '@/lib/api';
import { Languages, LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

interface Application {
    id: number;
    reference_number: string;
}

const props = withDefaults(defineProps<{
    application: Application;
    levels: string[];
    initialChinese?: Record<string, unknown> | null;
    apiBase?: string;
}>(), {
    apiBase: '/public/enrollments',
});

const emit = defineEmits<{ submitted: [data: { chinese_details: Record<string, unknown> }] }>();

const form = ref({
    grade_level: '',
    english_name: '',
    chinese_name: '',
    father_chinese_name: '',
    mother_chinese_name: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;

    const payload = {
        grade_level: form.value.grade_level.trim(),
        english_name: form.value.english_name.trim() || null,
        chinese_name: form.value.chinese_name.trim() || null,
        father_chinese_name: form.value.father_chinese_name.trim() || null,
        mother_chinese_name: form.value.mother_chinese_name.trim() || null,
    };

    try {
        await api.put<{ data: { chinese_details: Record<string, unknown> } }>(
            `${props.apiBase}/${props.application.id}/details`,
            { chinese_details: payload },
        );
        toast.success('Chinese class details saved.');
        emit('submitted', { chinese_details: payload });
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
    const chinese = props.initialChinese;

    if (!chinese) {
        return;
    }

    form.value = {
        grade_level: String(chinese.grade_level ?? ''),
        english_name: String(chinese.english_name ?? ''),
        chinese_name: String(chinese.chinese_name ?? ''),
        father_chinese_name: String(chinese.father_chinese_name ?? ''),
        mother_chinese_name: String(chinese.mother_chinese_name ?? ''),
    };
});
</script>

<template>
    <Card class="relative overflow-hidden border-border/60 bg-card/60">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

        <form @submit.prevent="submit">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Languages class="size-5 text-primary" />
                    For students taking Chinese classes
                </CardTitle>
                <CardDescription>
                    Please ask your child/ren their Chinese name that they use in class.
                </CardDescription>
            </CardHeader>

            <CardContent class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="chinese_grade">年级 (Incoming Grade Level)</Label>
                    <Combobox
                        :options="levels.map((level) => ({ value: level, label: level }))"
                        :model-value="form.grade_level"
                        placeholder="Begin typing for results"
                        @update:model-value="form.grade_level = String($event)"
                    />
                    <InputError :message="errors.grade_level" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="english_name">英文姓名 (English Name)</Label>
                        <Input id="english_name" v-model="form.english_name" type="text" placeholder="e.g. Juan Dela Cruz" class="h-9 px-2.5 py-1.5" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="chinese_name">中文姓名 (Chinese Name)</Label>
                        <Input id="chinese_name" v-model="form.chinese_name" type="text" placeholder="e.g. 林明" class="h-9 px-2.5 py-1.5" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="father_chinese_name">父亲姓名 (Father's Chinese Name if available)</Label>
                        <Input id="father_chinese_name" v-model="form.father_chinese_name" type="text" class="h-9 px-2.5 py-1.5" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="mother_chinese_name">母亲姓名 (Mother's Chinese Name if available)</Label>
                        <Input id="mother_chinese_name" v-model="form.mother_chinese_name" type="text" class="h-9 px-2.5 py-1.5" />
                    </div>
                </div>

                <Button type="submit" class="h-10" :disabled="processing">
                    <LoaderCircle v-if="processing" class="size-4 animate-spin" />
                    {{ processing ? 'Saving…' : 'Continue to school agreement' }}
                </Button>
            </CardContent>
        </form>
    </Card>
</template>