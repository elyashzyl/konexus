<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { CrudField } from '@/types/crud';
import { computed, reactive, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string;
        fields: CrudField[];
        initialValues?: Record<string, any> | null;
        options: Record<string, { value: string | number; label: string }[]>;
        submitting?: boolean;
        fieldErrors?: Record<string, string[]>;
        submitLabel?: string;
        contentClass?: string;
    }>(),
    {
        description: '',
        initialValues: null,
        options: () => ({}),
        submitting: false,
        fieldErrors: () => ({}),
        submitLabel: 'Save',
        contentClass: 'max-w-2xl',
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'submit', payload: Record<string, unknown>): void;
}>();

const form = reactive<Record<string, any>>({});

const fieldErrors = computed<Record<string, string[]>>(() => props.fieldErrors ?? {});

function defaultValue(field: CrudField): unknown {
    if (field.type === 'switch') {
        return false;
    }

    return '';
}

function resetForm(): void {
    const source = props.initialValues ?? {};

    for (const field of props.fields) {
        form[field.name] = source[field.name] !== undefined ? source[field.name] : defaultValue(field);
    }
}

watch(
    () => props.open,
    (open) => {
        if (open) {
            resetForm();
        }
    },
);

watch(
    () => props.initialValues,
    () => {
        if (props.open) {
            resetForm();
        }
    },
);

function buildPayload(): Record<string, unknown> {
    const payload: Record<string, unknown> = {};

    for (const field of props.fields) {
        const value = form[field.name];

        if (field.type === 'switch') {
            payload[field.name] = Boolean(value);
            continue;
        }

        if (field.type === 'number') {
            const parsed = value === '' || value === null || value === undefined ? Number.NaN : Number(value);

            payload[field.name] = Number.isNaN(parsed) ? undefined : parsed;
            continue;
        }

        const trimmed = typeof value === 'string' ? value.trim() : value;

        if (trimmed === '') {
            payload[field.name] = undefined;
            continue;
        }

        payload[field.name] = trimmed;
    }

    return payload;
}

function handleSubmit(): void {
    emit('submit', buildPayload());
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent :class="contentClass">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{ description }}</DialogDescription>
            </DialogHeader>

            <form id="crud-form" class="grid gap-4 py-2" @submit.prevent="handleSubmit">
                <div v-for="field in fields" :key="field.name" :class="field.fullWidth ? 'col-span-full' : 'grid-cols-2'">
                    <template v-if="field.type === 'switch'">
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div>
                                <Label :for="`field-${field.name}`" class="font-medium">{{ field.label }}</Label>
                                <p v-if="field.hint" class="text-xs text-muted-foreground">{{ field.hint }}</p>
                            </div>
                            <Switch :id="`field-${field.name}`" v-model="form[field.name]" />
                        </div>
                    </template>

                    <template v-else>
                        <Label :for="`field-${field.name}`" class="mb-1.5 block">
                            {{ field.label }}
                            <span v-if="field.required" class="text-destructive"> *</span>
                        </Label>

                        <Select v-if="field.type === 'select'" v-model="form[field.name]">
                            <SelectTrigger :id="`field-${field.name}`">
                                <SelectValue :placeholder="field.placeholder ?? 'Select…'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in field.options ?? options[field.name] ?? []"
                                    :key="String(option.value)"
                                    :value="String(option.value)"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Textarea
                            v-else-if="field.type === 'textarea'"
                            :id="`field-${field.name}`"
                            v-model="form[field.name]"
                            :placeholder="field.placeholder"
                        />

                        <Input
                            v-else
                            :id="`field-${field.name}`"
                            v-model="form[field.name]"
                            :type="field.type === 'number' ? 'number' : (field.type ?? 'text')"
                            :placeholder="field.placeholder"
                        />

                        <InputError :message="fieldErrors[field.name]?.[0]" />
                    </template>
                </div>
            </form>

            <DialogFooter>
                <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                <Button type="submit" form="crud-form" :disabled="submitting">
                    {{ submitting ? 'Saving…' : submitLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
