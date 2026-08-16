<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { STAFF_PORTALS } from '@/config/staffPortals';
import { ROLE_HOME_PATHS } from '@/lib/roles';
import { useAuthStore } from '@/stores/auth';
import { ArrowUpRight, BarChart3, GraduationCap, HeartHandshake, Layers, ShieldCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const auth = useAuthStore();

const welcome = computed(() => {
    const firstName = auth.user?.name.split(' ')[0] ?? 'there';

    return `Welcome back, ${firstName}`;
});

const roleLabel = computed(() => auth.primaryRole?.label ?? 'Administrator');

const stats = [
    { title: 'Students', value: '—', icon: GraduationCap, href: '/admin/dashboard' },
    { title: 'Faculty', value: '—', icon: Users, href: '/admin/dashboard' },
    { title: 'Classes', value: '—', icon: Layers, href: '/admin/dashboard' },
    { title: 'Reports', value: '—', icon: BarChart3, href: '/admin/reports' },
];

const portalLinks = computed(() => [
    { title: 'Student Portal', description: 'Grades, schedule and enrollment history.', href: '/portal/student', icon: GraduationCap },
    { title: 'Parent Portal', description: 'Linked children and family records.', href: '/portal/parent', icon: HeartHandshake },
    { title: 'Teacher Portal', description: 'Classes, schedule and advisory rosters.', href: '/portal/teacher', icon: Users },
    ...STAFF_PORTALS.map((portal) => ({
        title: portal.label,
        description: portal.description,
        href: ROLE_HOME_PATHS[portal.role] ?? `/portal/staff/${portal.role}`,
        icon: Users,
    })),
]);
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="ShieldCheck"
                :title="welcome"
                eyebrow="Administration"
                description="Signed in as an administrator. The school office, records and operational analytics are at your fingertips."
            />

            <section class="portal-rise mt-12">
                <div class="flex items-end justify-between gap-6">
                    <div>
                        <p class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase">School at a glance</p>
                        <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">Module overview</h2>
                    </div>
                </div>

                <div class="portal-rise mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" style="animation-delay: 120ms">
                    <RouterLink
                        v-for="(stat, index) in stats"
                        :key="stat.title"
                        :to="stat.href"
                        class="group relative overflow-hidden rounded-2xl border border-border/60 bg-card/60 p-6 transition-colors hover:border-primary/25"
                    >
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <div class="flex items-center justify-between">
                            <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                            <ArrowUpRight class="size-4 text-muted-foreground/50 transition-colors group-hover:text-primary" />
                        </div>
                        <component :is="stat.icon" class="mt-5 size-5 text-primary" />
                        <p class="mt-4 text-[13px] font-medium text-muted-foreground">{{ stat.title }}</p>
                        <p class="mt-1 font-display text-4xl font-medium tracking-[-0.02em] text-foreground">{{ stat.value }}</p>
                    </RouterLink>
                </div>
            </section>

            <section class="portal-rise mt-14" style="animation-delay: 200ms">
                <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                    <Card class="relative overflow-hidden border-border/60 bg-card/60">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 font-display text-lg font-medium tracking-[-0.01em]">
                                <ShieldCheck class="size-4 text-primary" /> Administration
                            </CardTitle>
                            <CardDescription>Operational tools for the school office.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-1.5">
                            <RouterLink
                                to="/admin/dashboard"
                                class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3 text-sm transition-colors hover:border-primary/25"
                            >
                                <span class="font-medium">Admin Dashboard</span>
                                <ArrowUpRight class="size-4 text-muted-foreground/50" />
                            </RouterLink>
                            <RouterLink
                                to="/admin/users"
                                class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3 text-sm transition-colors hover:border-primary/25"
                            >
                                <span class="font-medium">Users & Roles</span>
                                <ArrowUpRight class="size-4 text-muted-foreground/50" />
                            </RouterLink>
                            <RouterLink
                                to="/admin/reports"
                                class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3 text-sm transition-colors hover:border-primary/25"
                            >
                                <span class="font-medium">Reports</span>
                                <ArrowUpRight class="size-4 text-muted-foreground/50" />
                            </RouterLink>
                            <RouterLink
                                to="/admin/maintenance"
                                class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3 text-sm transition-colors hover:border-primary/25"
                            >
                                <span class="font-medium">Maintenance</span>
                                <ArrowUpRight class="size-4 text-muted-foreground/50" />
                            </RouterLink>
                        </CardContent>
                    </Card>

                    <Card class="relative overflow-hidden border-border/60 bg-card/60">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 font-display text-lg font-medium tracking-[-0.01em]">
                                <BarChart3 class="size-4 text-primary" /> Your role
                            </CardTitle>
                            <CardDescription>Current account context.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="rounded-xl border border-border/60 p-4">
                                <p class="text-[11px] font-medium tracking-[0.2em] text-muted-foreground uppercase">Role</p>
                                <p class="mt-1.5 font-display text-xl font-medium text-foreground">{{ roleLabel }}</p>
                            </div>
                            <div class="rounded-xl border border-border/60 p-4">
                                <p class="text-[11px] font-medium tracking-[0.2em] text-muted-foreground uppercase">Email</p>
                                <p class="mt-1.5 text-sm text-foreground">{{ auth.user?.email }}</p>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <ShieldCheck class="size-3.5 text-primary" />
                                Administrative access only
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <section class="portal-rise mt-14" style="animation-delay: 240ms">
                <div>
                    <p class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase">Portals</p>
                    <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">Browse every portal</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                        Preview the student, parent, teacher and staff workspaces from one place.
                    </p>
                </div>

                <div class="portal-rise mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <RouterLink
                        v-for="portal in portalLinks"
                        :key="portal.href"
                        :to="portal.href"
                        class="group relative overflow-hidden rounded-2xl border border-border/60 bg-card/60 p-6 transition-colors hover:border-primary/25"
                    >
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                        <div class="flex items-center justify-between">
                            <component :is="portal.icon" class="size-5 text-primary" />
                            <ArrowUpRight class="size-4 text-muted-foreground/50 transition-colors group-hover:text-primary" />
                        </div>
                        <p class="mt-5 font-display text-lg font-medium tracking-[-0.01em] text-foreground">{{ portal.title }}</p>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ portal.description }}</p>
                    </RouterLink>
                </div>
            </section>

            <footer class="portal-rise mt-20 border-t border-border/60 pt-6" style="animation-delay: 260ms">
                <div class="flex flex-col justify-between gap-3 text-xs text-muted-foreground sm:flex-row sm:items-center">
                    <p>Protect your account and do not share your password.</p>
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="size-3.5 text-primary" />
                        Konexus administration
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>