<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { extractError } from '@/lib/api';
import { platformApi, type SettingsGroup } from '@/lib/platformApi';
import { Check, Save, Settings2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const saving = ref(false);
const groups = ref<SettingsGroup[]>([]);
const activeGroup = ref<string>('');
const draft = ref<Record<string, string>>({});
const savedKeys = ref<Set<string>>(new Set());

onMounted(async () => {
    try {
        groups.value = await platformApi.settings.index();
        activeGroup.value = groups.value[0]?.group ?? '';
        for (const group of groups.value) {
            for (const setting of group.settings) {
                draft.value[setting.key] = setting.value ?? '';
            }
        }
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

const active = () => groups.value.find((group) => group.group === activeGroup.value);

async function saveGroup(): Promise<void> {
    const group = active();
    if (!group) return;

    saving.value = true;
    try {
        const payload: Record<string, string> = {};
        for (const setting of group.settings) {
            payload[setting.key] = draft.value[setting.key] ?? '';
        }

        await platformApi.settings.update(payload);
        savedKeys.value = new Set(group.settings.map((setting) => setting.key));
        toast.success('Settings saved.');
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="Settings2"
                index="04"
                eyebrow="Configuration"
                title="Settings"
                description="Grouped system configuration."
            />

            <div v-if="loading" class="portal-rise mt-10 space-y-2">
                <Skeleton v-for="i in 6" :key="i" class="h-12" />
            </div>

            <template v-else>
                <div class="portal-rise mt-10 flex flex-wrap gap-2 border-b border-border/60 pb-3">
                    <Button
                        v-for="group in groups"
                        :key="group.group"
                        variant="ghost"
                        size="sm"
                        :class="activeGroup === group.group ? 'bg-muted text-foreground' : ''"
                        @click="activeGroup = group.group"
                    >
                        {{ group.label }}
                    </Button>
                </div>

                <Card v-if="active()" class="portal-rise relative mt-6 overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">{{ active()!.label }}</CardTitle>
                            <CardDescription>{{ active()!.settings.length }} settings</CardDescription>
                        </div>
                        <Button :disabled="saving" @click="saveGroup">
                            <Save class="size-4" /> {{ saving ? 'Saving…' : 'Save group' }}
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="setting in active()!.settings" :key="setting.key" class="grid gap-3 rounded-xl border border-border/60 px-4 py-3 md:grid-cols-2">
                            <div>
                                <p class="text-sm font-medium">{{ setting.label }}</p>
                                <p class="text-xs text-muted-foreground">{{ setting.key }}</p>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <template v-if="setting.type === 'select'">
                                    <Select :model-value="draft[setting.key] ?? ''" @update:model-value="(v: string) => (draft[setting.key] = v)">
                                        <SelectTrigger class="w-56">
                                            <SelectValue :placeholder="draft[setting.key] ?? 'Select…'" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in setting.options" :key="option" :value="option">{{ option }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </template>
                                <template v-else-if="setting.type === 'boolean'">
                                    <Switch :model-value="draft[setting.key] === '1' || draft[setting.key] === 'true'" @update:model-value="(v: boolean) => (draft[setting.key] = v ? '1' : '0')" />
                                </template>
                                <template v-else>
                                    <Input v-model="draft[setting.key]" class="w-56" />
                                </template>

                                <span v-if="savedKeys.has(setting.key)" class="flex items-center gap-1 text-xs text-emerald-600">
                                    <Check class="size-3.5" /> Saved
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </div>
</template>
