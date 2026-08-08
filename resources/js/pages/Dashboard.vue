<script setup lang="ts">
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAuthStore } from '@/stores/auth';
import { BarChart3, GraduationCap, Layers, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const auth = useAuthStore();

const welcome = computed(() => {
    const firstName = auth.user?.name.split(' ')[0] ?? 'there';

    return `Welcome back, ${firstName}`;
});

const stats = [
    { title: 'Students', value: '—', icon: GraduationCap },
    { title: 'Faculty', value: '—', icon: Users },
    { title: 'Classes', value: '—', icon: Layers },
    { title: 'Reports', value: '—', icon: BarChart3 },
];
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">{{ welcome }}</h1>
            <p class="text-sm text-muted-foreground">
                Signed in as <span class="font-medium text-foreground">{{ auth.primaryRole?.label }}</span
                >. Module dashboards will appear here.
            </p>
        </div>

        <div class="grid auto-rows-min gap-4 sm:grid-cols-2 md:grid-cols-4">
            <Card v-for="stat in stats" :key="stat.title">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                    <component :is="stat.icon" class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stat.value }}</div>
                    <CardDescription>Awaiting module data</CardDescription>
                </CardContent>
            </Card>
        </div>

        <div class="relative min-h-[50vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <PlaceholderPattern />
        </div>
    </div>
</template>
