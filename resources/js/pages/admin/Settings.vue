<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { extractError } from '@/lib/api';
import { platformApi, type SettingsGroup } from '@/lib/platformApi';
import { subscriptionApi, type SubscriptionSettingItem } from '@/lib/subscriptionApi';
import { Check, Save, Settings2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const scope = ref<'system' | 'subscription'>('system');

const loading = ref(true);
const saving = ref(false);
const groups = ref<SettingsGroup[]>([]);
const activeGroup = ref<string>('');
const draft = ref<Record<string, string>>({});
const savedKeys = ref<Set<string>>(new Set());

const subLoading = ref(false);
const subSaving = ref(false);
const subGroups = ref<Record<string, SubscriptionSettingItem[]>>({});
const subDirty = ref<Set<string>>(new Set());

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

    await loadSubSettings();
});

async function loadSubSettings(): Promise<void> {
    subLoading.value = true;
    try {
        subGroups.value = await subscriptionApi.settings.grouped();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        subLoading.value = false;
    }
}

function switchScope(next: 'system' | 'subscription'): void {
    scope.value = next;
}

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

const subIsBool = (s: SubscriptionSettingItem) => s.type === 'boolean';
const subIsText = (s: SubscriptionSettingItem) => s.type === 'text';
const subIsNumber = (s: SubscriptionSettingItem) => ['number', 'integer', 'decimal', 'float'].includes(s.type);
const subIsArray = (s: SubscriptionSettingItem) => s.type === 'array';

function subSetValue(setting: SubscriptionSettingItem, value: string | number | boolean | number[] | null): void {
    setting.value = value;
    subDirty.value.add(setting.key);
}

async function saveSubSettings(): Promise<void> {
    if (!subDirty.value.size) return;
    subSaving.value = true;
    const payload: Record<string, string | number | boolean | number[] | null> = {};
    for (const group of Object.values(subGroups.value)) {
        for (const setting of group) {
            if (subDirty.value.has(setting.key)) payload[setting.key] = setting.value;
        }
    }
    try {
        subGroups.value = await subscriptionApi.settings.bulk(payload);
        subDirty.value.clear();
        toast.success('Settings saved.');
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        subSaving.value = false;
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

            <div class="portal-rise mt-8 inline-flex rounded-lg border border-border/60 bg-muted/40 p-1">
                <Button
                    variant="ghost"
                    size="sm"
                    :class="scope === 'system' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground'"
                    @click="switchScope('system')"
                >
                    System settings
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    :class="scope === 'subscription' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground'"
                    @click="switchScope('subscription')"
                >
                    Subscription settings
                </Button>
            </div>

            <template v-if="scope === 'system'">
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
            </template>

            <template v-else>
                <section v-if="subLoading" class="portal-rise mt-10 space-y-4">
                    <Skeleton v-for="i in 3" :key="i" class="h-40" />
                </section>

                <section v-else class="mt-10 space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">Grace windows, expiry behavior, notice thresholds and defaults.</p>
                        <Button :disabled="!subDirty.size || subSaving" @click="saveSubSettings">
                            <Save class="size-4" /> {{ subSaving ? 'Saving…' : `Save (${subDirty.size})` }}
                        </Button>
                    </div>
                    <Card v-for="(settings, group) in subGroups" :key="group" class="relative overflow-hidden border-border/60 bg-card/60">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <CardHeader>
                            <CardTitle class="font-display text-lg font-medium capitalize tracking-[-0.01em]">{{ group }}</CardTitle>
                        </CardHeader>
                        <CardContent class="divide-y divide-border/60">
                            <div v-for="setting in settings" :key="setting.key" class="flex items-start justify-between gap-6 py-4">
                                <div class="min-w-0 max-w-md">
                                    <div class="flex items-center gap-2">
                                        <p class="font-mono text-sm font-medium">{{ setting.key }}</p>
                                        <Badge v-if="subDirty.has(setting.key)" variant="secondary">modified</Badge>
                                    </div>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ setting.description }}</p>
                                </div>
                                <div class="w-64 shrink-0">
                                    <Switch v-if="subIsBool(setting)" :checked="Boolean(setting.value)" @update:checked="subSetValue(setting, $event)" />
                                    <Input
                                        v-else-if="subIsNumber(setting)"
                                        type="number"
                                        step="any"
                                        :model-value="setting.value === null || setting.value === undefined ? '' : String(setting.value)"
                                        @update:model-value="subSetValue(setting, $event === '' ? null : Number($event))"
                                    />
                                    <Textarea
                                        v-else-if="subIsText(setting)"
                                        :model-value="setting.value === null || setting.value === undefined ? '' : String(setting.value)"
                                        rows="2"
                                        @update:model-value="subSetValue(setting, $event)"
                                    />
                                    <Input
                                        v-else-if="subIsArray(setting)"
                                        :model-value="Array.isArray(setting.value) ? (setting.value as number[]).join(',') : ''"
                                        placeholder="Comma-separated values"
                                        @update:model-value="
                                            subSetValue(
                                                setting,
                                                String($event)
                                                    .split(',')
                                                    .map((s: string) => Number(s.trim()))
                                                    .filter((n: number) => Number.isFinite(n)),
                                            )
                                        "
                                    />
                                    <Input
                                        v-else
                                        :model-value="setting.value === null || setting.value === undefined ? '' : String(setting.value)"
                                        @update:model-value="subSetValue(setting, $event)"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </section>
            </template>
        </div>
    </div>
</template>
