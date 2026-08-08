<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description: string;
        confirmLabel?: string;
        destructive?: boolean;
        busy?: boolean;
    }>(),
    {
        confirmLabel: 'Confirm',
        destructive: false,
        busy: false,
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                <Button :variant="destructive ? 'destructive' : 'default'" :disabled="busy" @click="emit('confirm')">
                    {{ busy ? 'Working…' : confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
