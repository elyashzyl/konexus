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
import api, { extractError } from '@/lib/api';
import { platformApi, type SchoolRef, type SettingsGroup } from '@/lib/platformApi';
import { subscriptionApi, type SubscriptionSettingItem } from '@/lib/subscriptionApi';
import { useAuthStore } from '@/stores/auth';
import { Check, Building2, CircleAlert, Save, Settings2 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const auth = useAuthStore();

const isSuperAdmin = computed(() => auth.can('super-administrator'));
const isPlatformOperator = computed(() => auth.can('super-administrator') || auth.can('platform-administrator'));

const scope = ref<'system' | 'subscription'>('system');

// System settings are per-school. Super administrators pick the school to
// configure; school administrators are always editing their own.
const systemSchoolId = ref<number | null>(null);
const systemSchoolName = ref('');
const schools = ref<SchoolRef[]>([]);

const loading = ref(true);
const saving = ref(false);
const groups = ref<SettingsGroup[]>([]);
const activeGroup = ref<string>('');
const draft = ref<Record<string, string>>({});
const initialDraft = ref<Record<string, string>>({});
const savedKeys = ref<Set<string>>(new Set());

let savedTimer: ReturnType<typeof setTimeout> | undefined;

const subLoading = ref(false);
const subSaving = ref(false);
const subGroups = ref<Record<string, SubscriptionSettingItem[]>>({});
const subDirty = ref<Set<string>>(new Set());
const subSchoolId = ref<number | null>(null);
const subSchoolName = ref('');

onMounted(async () => {
    if (isPlatformOperator.value) {
        try {
            const { data } = await api.get<{ data: { items: SchoolRef[] } }>('/school-profiles', {
                params: { per_page: 100 },
            });
            schools.value = data.data.items;
        } catch (error) {
            toast.error(extractError(error));
        }
    }

    if (isSuperAdmin.value) {
        await selectSchool(schools.value[0]?.id ?? null);
    } else {
        await selectSchool(auth.user?.school_profile_id ?? null);
    }

    subSchoolId.value = isPlatformOperator.value ? (schools.value[0]?.id ?? null) : null;
    subSchoolName.value = schools.value[0]?.name ?? '';

    await loadSubSettings();
});

async function selectSchool(schoolId: number | null): Promise<void> {
    systemSchoolId.value = schoolId;
    await loadSystemSettings();
}

async function loadSystemSettings(): Promise<void> {
    loading.value = true;
    savedKeys.value = new Set();

    try {
        const data = await platformApi.settings.index(systemSchoolId.value);

        systemSchoolId.value = data.school?.id ?? null;
        systemSchoolName.value = data.school?.name ?? '';

        groups.value = data.groups;
        activeGroup.value = groups.value[0]?.group ?? '';
        draft.value = {};
        initialDraft.value = {};

        for (const group of groups.value) {
            for (const setting of group.settings) {
                const value = setting.value ?? '';
                draft.value[setting.key] = value;
                initialDraft.value[setting.key] = value;
            }
        }
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
}

async function loadSubSettings(): Promise<void> {
    subLoading.value = true;
    try {
        subGroups.value = await subscriptionApi.settings.grouped(subSchoolId.value);
        subDirty.value.clear();
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        subLoading.value = false;
    }
}

function selectSubSchool(schoolId: number): void {
    subSchoolId.value = schoolId;
    subSchoolName.value = schools.value.find((school) => school.id === schoolId)?.name ?? '';
    loadSubSettings();
}

function switchScope(next: 'system' | 'subscription'): void {
    scope.value = next;
}

const active = () => groups.value.find((group) => group.group === activeGroup.value);

const isDirty = (key: string): boolean => draft.value[key] !== initialDraft.value[key];

const dirtyCount = (group: SettingsGroup): number => group.settings.filter((setting) => isDirty(setting.key)).length;

function setDraft(key: string, value: string): void {
    draft.value[key] = value;

    if (savedKeys.value.has(key)) {
        savedKeys.value = new Set([...savedKeys.value].filter((saved) => saved !== key));
    }
}

const boolOn = (key: string): boolean => draft.value[key] === '1' || draft.value[key] === 'true';

function setBool(key: string, on: boolean): void {
    setDraft(key, on ? '1' : '0');
}

function markSaved(keys: string[]): void {
    savedKeys.value = new Set(keys);
    if (savedTimer) clearTimeout(savedTimer);
    savedTimer = setTimeout(() => {
        savedKeys.value = new Set();
    }, 3000);
}

async function saveGroup(): Promise<void> {
    const group = active();
    if (!group) return;

    saving.value = true;
    try {
        const payload: Record<string, string> = {};
        for (const setting of group.settings) {
            payload[setting.key] = draft.value[setting.key] ?? '';
        }

        await platformApi.settings.update(payload, systemSchoolId.value);
        for (const setting of group.settings) {
            initialDraft.value[setting.key] = draft.value[setting.key] ?? '';
        }
        markSaved(group.settings.map((setting) => setting.key));
        toast.success(`${group.label} settings saved.`);
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

const titleCaseWords = (slug: string): string =>
    slug
        .split(/[_\-]+/)
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const subGroupLabel = (group: string): string => titleCaseWords(group);

const subSettingLabel = (key: string): string => {
    const tail = key.includes('.') ? key.slice(key.indexOf('.') + 1) : key;
    return titleCaseWords(tail);
};

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
        subGroups.value = await subscriptionApi.settings.bulk(payload, subSchoolId.value);
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
                description="How your school runs day to day, and the subscription rules that govern billing and access."
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
                    v-if="isPlatformOperator"
                    variant="ghost"
                    size="sm"
                    :class="scope === 'subscription' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground'"
                    @click="switchScope('subscription')"
                >
                    Subscription settings
                </Button>
            </div>

            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                <template v-if="scope === 'system'">
                    Day-to-day school configuration — identity, academic calendar, enrollment behavior, notifications and portal access. Changes apply only to the selected school.
                </template>
                <template v-else>
                    Billing rules that shape how each school's subscription behaves — grace periods, expiry warnings and access defaults. These are platform-level overrides applied per school.
                </template>
            </p>

            <template v-if="scope === 'system'">
                <div class="portal-rise mt-8 flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card/60 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <Building2 class="size-4 text-muted-foreground" />
                        <span class="text-sm font-medium">School:</span>
                    </div>

                    <template v-if="isSuperAdmin">
                        <Select
                            :model-value="String(systemSchoolId ?? '')"
                            @update:model-value="(value: string) => selectSchool(Number(value))"
                        >
                            <SelectTrigger class="w-72">
                                <SelectValue placeholder="Select a school…" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="school in schools" :key="school.id" :value="String(school.id)">
                                    {{ school.name }}{{ school.short_name ? ` (${school.short_name})` : '' }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </template>
                    <template v-else>
                        <span class="text-sm text-muted-foreground">{{ systemSchoolName || 'Not linked to a school' }}</span>
                    </template>

                    <span v-if="systemSchoolName" class="ml-auto hidden text-xs text-muted-foreground sm:block">
                        Editing settings for <span class="font-medium text-foreground">{{ systemSchoolName }}</span>
                    </span>
                </div>

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
                            <span
                                v-if="dirtyCount(group) > 0"
                                class="ml-1 inline-flex min-w-4 items-center justify-center rounded-full bg-amber-500/15 px-1 font-mono text-[10px] leading-4 text-amber-600"
                            >
                                {{ dirtyCount(group) }}
                            </span>
                        </Button>
                    </div>

                    <Card v-if="active()" class="portal-rise relative mt-6 overflow-hidden border-border/60 bg-card/60">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <CardHeader class="flex flex-row items-start justify-between gap-4">
                            <div>
                                <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">{{ active()!.label }}</CardTitle>
                                <CardDescription class="mt-1 max-w-xl">
                                    {{ active()!.description }}
                                    <span
                                        v-if="dirtyCount(active()!) > 0"
                                        class="mt-2 flex items-center gap-1.5 text-amber-600"
                                    >
                                        <CircleAlert class="size-3.5" />
                                        You have {{ dirtyCount(active()!) }} unsaved change{{ dirtyCount(active()!) === 1 ? '' : 's' }} in this section.
                                    </span>
                                </CardDescription>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    v-if="dirtyCount(active()!) > 0"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-600"
                                >
                                    <CircleAlert class="size-3.5" />
                                    Unsaved changes
                                </span>
                                <Button :disabled="saving || dirtyCount(active()!) === 0" @click="saveGroup">
                                    <Save class="size-4" /> {{ saving ? 'Saving…' : 'Save changes' }}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-for="setting in active()!.settings"
                                :key="setting.key"
                                class="grid gap-3 rounded-xl border border-border/60 px-4 py-3 transition-colors md:grid-cols-2"
                                :class="isDirty(setting.key) ? 'border-amber-400/60 bg-amber-500/[0.04]' : ''"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium">{{ setting.label }}</p>
                                        <span v-if="savedKeys.has(setting.key)" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
                                            <Check class="size-3.5" /> Saved
                                        </span>
                                        <span v-else-if="isDirty(setting.key)" class="inline-flex items-center gap-1 text-xs font-medium text-amber-600">
                                            <CircleAlert class="size-3.5" /> Unsaved
                                        </span>
                                    </div>
                                    <p v-if="setting.description" class="mt-0.5 text-xs text-muted-foreground">{{ setting.description }}</p>
                                </div>

                                <div class="flex items-center justify-end gap-3">
                                    <template v-if="setting.type === 'select'">
                                        <Select :model-value="draft[setting.key] ?? ''" @update:model-value="(value: string) => setDraft(setting.key, value)">
                                            <SelectTrigger class="w-56">
                                                <SelectValue placeholder="Select…" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="option in setting.options" :key="option.value" :value="option.value">{{ option.label }}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </template>
                                    <template v-else-if="setting.type === 'boolean'">
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-medium" :class="boolOn(setting.key) ? 'text-emerald-600' : 'text-muted-foreground'">
                                                {{ boolOn(setting.key) ? 'Enabled' : 'Disabled' }}
                                            </span>
                                            <Switch :checked="boolOn(setting.key)" @update:checked="(value: boolean) => setBool(setting.key, value)" />
                                        </div>
                                    </template>
                                    <template v-else>
                                        <Input
                                            :model-value="draft[setting.key] ?? ''"
                                            class="w-56"
                                            @update:model-value="(value: string | number) => setDraft(setting.key, String(value))"
                                        />
                                    </template>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </template>
            </template>

            <template v-else>
                <div v-if="isPlatformOperator" class="portal-rise mt-8 flex flex-wrap items-center gap-3 rounded-xl border border-border/60 bg-card/60 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <Building2 class="size-4 text-muted-foreground" />
                        <span class="text-sm font-medium">School:</span>
                    </div>

                    <Select
                        :model-value="String(subSchoolId ?? '')"
                        @update:model-value="(value: string) => selectSubSchool(Number(value))"
                    >
                        <SelectTrigger class="w-72">
                            <SelectValue placeholder="Select a school…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="school in schools" :key="school.id" :value="String(school.id)">
                                {{ school.name }}{{ school.short_name ? ` (${school.short_name})` : '' }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <span v-if="subSchoolName" class="ml-auto hidden text-xs text-muted-foreground sm:block">
                        Editing subscription settings for <span class="font-medium text-foreground">{{ subSchoolName }}</span>
                    </span>
                </div>

                <section v-if="subLoading" class="portal-rise mt-10 space-y-4">
                    <Skeleton v-for="i in 3" :key="i" class="h-40" />
                </section>

                <section v-else class="mt-10 space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="max-w-xl text-sm text-muted-foreground">Grace periods, expiry warnings and access defaults for the selected school. Only changed settings are saved.</p>
                        <Button :disabled="!subDirty.size || subSaving" @click="saveSubSettings">
                            <Save class="size-4" /> {{ subSaving ? 'Saving…' : `Save changes (${subDirty.size})` }}
                        </Button>
                    </div>
                    <Card v-for="(settings, group) in subGroups" :key="group" class="relative overflow-hidden border-border/60 bg-card/60">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <CardHeader class="flex flex-row items-center justify-between gap-4 space-y-0">
                            <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">{{ subGroupLabel(String(group)) }}</CardTitle>
                            <span class="rounded-full border border-primary/15 bg-primary/5 px-3 py-1 font-mono text-xs text-primary">
                                {{ settings.length }} {{ settings.length === 1 ? 'setting' : 'settings' }}
                            </span>
                        </CardHeader>
                        <CardContent class="divide-y divide-border/60">
                            <div v-for="setting in settings" :key="setting.key" class="flex items-start justify-between gap-6 py-4">
                                <div class="min-w-0 max-w-md">
                                    <p class="text-sm font-medium">{{ subSettingLabel(setting.key) }}</p>
                                    <p class="mt-0.5 font-mono text-[11px] text-muted-foreground">{{ setting.key }}</p>
                                    <p class="mt-1.5 text-xs leading-relaxed text-muted-foreground">{{ setting.description || 'No description provided.' }}</p>
                                    <Badge
                                        v-if="subDirty.has(setting.key)"
                                        variant="outline"
                                        class="mt-2 border-amber-300/60 bg-amber-500/10 text-[10px] uppercase tracking-wide text-amber-600"
                                    >
                                        Unsaved
                                    </Badge>
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
