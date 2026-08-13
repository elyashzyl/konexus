<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { AUTH_ROUTES, APP_ROUTES } from '@/constants/app';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    Building2,
    CalendarDays,
    CalendarRange,
    DoorOpen,
    GraduationCap,
    Layers,
    Megaphone,
    Network,
    School,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';
import { RouterLink } from 'vue-router';

const navLinks = [
    { label: 'Platform', href: '#platform' },
    { label: 'Modules', href: '#modules' },
    { label: 'Portals', href: '#portals' },
];

const stats = [
    { value: '01', label: 'Management system', detail: 'Built for the school office.' },
    { value: '08', label: 'Staff portals', detail: 'One role, one workspace.' },
    { value: '03', label: 'Community portals', detail: 'Students, parents & teachers.' },
    { value: '24/7', label: 'On the web', detail: 'From any device, anywhere.' },
];

const features = [
    {
        icon: School,
        title: 'School foundation',
        description: 'Campus, buildings, rooms, departments, grade levels, sections and academic years — the structure behind every record.',
    },
    {
        icon: CalendarDays,
        title: 'Academic calendar',
        description: 'Years, terms and class schedules that keep enrolment, grading and reporting in step with the school year.',
    },
    {
        icon: BookOpen,
        title: 'Subjects & classes',
        description: 'A living catalogue of subjects, sections and schedules, ready for teachers and advisers to work from.',
    },
    {
        icon: Users,
        title: 'People & roles',
        description: 'Students, faculty, staff and parents each get a clear role — and a workspace that matches it.',
    },
    {
        icon: Megaphone,
        title: 'Announcements',
        description: 'Share notices with the school community and keep everyone reading from the same page.',
    },
    {
        icon: BarChart3,
        title: 'Reports & activity',
        description: 'Operational reports and a full activity trail so the school office always knows what happened and when.',
    },
];

const portals = [
    {
        icon: GraduationCap,
        title: 'Student portal',
        description: 'Grades, schedule and announcements in one quiet place.',
    },
    {
        icon: Users,
        title: 'Parent portal',
        description: 'Follow your child\u2019s progress without the paper trail.',
    },
    {
        icon: BookOpen,
        title: 'Teacher portal',
        description: 'Classes, rosters and records at the ready.',
    },
    {
        icon: ShieldCheck,
        title: 'Administration',
        description: 'Users, school records and analytics under one roof.',
    },
];
</script>

<template>
    <div class="relative min-h-svh bg-background">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-[34rem] bg-[radial-gradient(60rem_26rem_at_50%_-30%,hsl(26_57%_40%/0.1),transparent)]"
        />

        <!-- Navigation -->
        <header class="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8">
                <RouterLink :to="APP_ROUTES.landing.path" class="flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <AppLogoIcon class="size-5" />
                    </span>
                    <span class="font-display text-lg font-medium tracking-[-0.01em] text-foreground">KONEXUS</span>
                </RouterLink>

                <nav class="hidden items-center gap-8 md:flex">
                    <a
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="text-[13px] font-medium text-muted-foreground transition-colors hover:text-foreground"
                    >
                        {{ link.label }}
                    </a>
                </nav>

                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" as-child>
                        <RouterLink :to="AUTH_ROUTES.login.path">Log in</RouterLink>
                    </Button>
                    <Button size="sm" as-child>
                        <RouterLink :to="AUTH_ROUTES.register.path">Get started</RouterLink>
                    </Button>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative mx-auto max-w-6xl px-5 pt-20 pb-16 sm:px-8 sm:pt-28">
            <div class="portal-rise mx-auto max-w-3xl text-center">
                <p class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase">School Management Information System</p>
                <h1 class="mt-6 font-display text-5xl leading-[1.02] font-medium tracking-[-0.02em] text-foreground sm:text-7xl">
                    The school office,<br />
                    in order.
                </h1>
                <p class="mx-auto mt-6 max-w-xl text-[15px] leading-7 text-muted-foreground">
                    KONEXUS brings the people, records and rhythm of a school into one calm system — campus structure, academic
                    years, classes and announcements, with a workspace for every role.
                </p>
                <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                    <Button size="lg" class="gap-2" as-child>
                        <RouterLink :to="AUTH_ROUTES.register.path">
                            Explore the system
                            <ArrowRight class="size-4" />
                        </RouterLink>
                    </Button>
                    <Button size="lg" variant="outline" as-child>
                        <RouterLink :to="AUTH_ROUTES.login.path">Sign in to your portal</RouterLink>
                    </Button>
                </div>
            </div>

            <div class="portal-rise mt-20" style="animation-delay: 140ms">
                <div class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <div class="grid grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="(stat, index) in stats"
                            :key="stat.label"
                            class="border-border/60 p-6 sm:p-8"
                            :class="index % 2 === 1 ? 'border-l' : ''"
                        >
                            <p class="index-num font-mono text-3xl font-medium text-primary sm:text-4xl">{{ stat.value }}</p>
                            <p class="mt-3 text-[13px] font-medium text-foreground">{{ stat.label }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ stat.detail }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Platform -->
        <section id="platform" class="relative mx-auto max-w-6xl px-5 py-20 sm:px-8">
            <div class="portal-rise flex items-end justify-between gap-6">
                <div>
                    <p class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase">No. 01 — The platform</p>
                    <h2 class="mt-4 font-display text-4xl font-medium tracking-[-0.02em] text-foreground sm:text-5xl">
                        Everything the office touches
                    </h2>
                </div>
                <p class="hidden max-w-sm text-sm leading-6 text-muted-foreground sm:block">
                    One system holds the school\u2019s structure, its calendar and its people — so nothing gets lost between departments.
                </p>
            </div>
            <div class="editorial-rule mt-10 h-px" />

            <div class="portal-rise mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" style="animation-delay: 120ms">
                <div
                    v-for="(feature, index) in features"
                    :key="feature.title"
                    class="group relative overflow-hidden rounded-2xl border border-border/60 bg-card/60 p-6 transition-colors hover:border-primary/25"
                >
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <div class="flex items-center justify-between">
                        <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                        <component :is="feature.icon" class="size-4 text-primary" />
                    </div>
                    <h3 class="mt-5 font-display text-xl font-medium tracking-[-0.01em] text-foreground">{{ feature.title }}</h3>
                    <p class="mt-2.5 text-sm leading-6 text-muted-foreground">{{ feature.description }}</p>
                </div>
            </div>
        </section>

        <!-- Modules -->
        <section id="modules" class="relative border-y border-border/60 bg-sidebar-background">
            <div class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
                <div class="portal-rise">
                    <p class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase">No. 02 — The modules</p>
                    <h2 class="mt-4 font-display text-4xl font-medium tracking-[-0.02em] text-foreground sm:text-5xl">
                        School records, structured
                    </h2>
                    <p class="mt-5 max-w-xl text-[15px] leading-7 text-muted-foreground">
                        From the campus map to the class schedule, KONEXUS keeps school data tidy, linked and searchable.
                    </p>
                </div>

                <div class="portal-rise mt-10 grid gap-px overflow-hidden rounded-2xl border border-border/60 bg-border/60" style="animation-delay: 120ms">
                    <RouterLink
                        v-for="(module, index) in [
                            { icon: School, label: 'School Profile', detail: 'The school itself', to: '/school/profile' },
                            { icon: Building2, label: 'Campuses & Buildings', detail: 'Places and floors', to: '/school/campuses' },
                            { icon: DoorOpen, label: 'Rooms', detail: 'Where classes meet', to: '/facilities/rooms' },
                            { icon: Network, label: 'Departments', detail: 'How staff is organized', to: '/school/departments' },
                            { icon: Layers, label: 'Grade Levels & Sections', detail: 'The class ladder', to: '/school/grade-levels' },
                            { icon: CalendarRange, label: 'Academic Years & Terms', detail: 'Time, kept', to: '/school/academic-years' },
                            { icon: CalendarDays, label: 'Class Schedules', detail: 'The week, arranged', to: '/calendar' },
                            { icon: Megaphone, label: 'Announcements', detail: 'Word from the office', to: '/announcements' },
                        ]"
                        :key="module.label"
                        :to="module.to"
                        class="group flex items-center justify-between gap-4 bg-card/60 p-5 transition-colors hover:bg-card sm:p-6"
                    >
                        <div class="flex items-center gap-4">
                            <span class="index-num font-mono text-xs text-muted-foreground/50">{{ String(index + 1).padStart(2, '0') }}</span>
                            <span class="flex size-9 items-center justify-center rounded-lg bg-primary/8 text-primary ring-1 ring-primary/10">
                                <component :is="module.icon" class="size-4" />
                            </span>
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ module.label }}</p>
                                <p class="text-xs text-muted-foreground">{{ module.detail }}</p>
                            </div>
                        </div>
                        <ArrowRight class="size-4 text-muted-foreground/40 transition-colors group-hover:text-primary" />
                    </RouterLink>
                </div>
            </div>
        </section>

        <!-- Portals -->
        <section id="portals" class="relative mx-auto max-w-6xl px-5 py-20 sm:px-8">
            <div class="portal-rise">
                <p class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase">No. 03 — The portals</p>
                <h2 class="mt-4 font-display text-4xl font-medium tracking-[-0.02em] text-foreground sm:text-5xl">
                    A workspace for every role
                </h2>
                <p class="mt-5 max-w-xl text-[15px] leading-7 text-muted-foreground">
                    Students, parents, teachers and the school office each see only what their role needs.
                </p>
            </div>

            <div class="portal-rise mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" style="animation-delay: 120ms">
                <div
                    v-for="portal in portals"
                    :key="portal.title"
                    class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/60 p-6"
                >
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <component :is="portal.icon" class="size-5 text-primary" />
                    <h3 class="mt-4 font-display text-lg font-medium tracking-[-0.01em] text-foreground">{{ portal.title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ portal.description }}</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="relative mx-auto max-w-6xl px-5 pb-20 sm:px-8">
            <div class="portal-rise relative overflow-hidden rounded-2xl border border-border/60 bg-card/60 px-6 py-16 text-center sm:px-12">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                <p class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase">Ready when you are</p>
                <h2 class="mx-auto mt-4 max-w-xl font-display text-4xl font-medium tracking-[-0.02em] text-foreground sm:text-5xl">
                    Put the school office in order.
                </h2>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Button size="lg" class="gap-2" as-child>
                        <RouterLink :to="AUTH_ROUTES.register.path">
                            Create an account
                            <ArrowRight class="size-4" />
                        </RouterLink>
                    </Button>
                    <Button size="lg" variant="outline" as-child>
                        <RouterLink :to="AUTH_ROUTES.login.path">Sign in</RouterLink>
                    </Button>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border/60">
            <div class="mx-auto flex max-w-6xl flex-col justify-between gap-4 px-5 py-8 text-xs text-muted-foreground sm:flex-row sm:items-center sm:px-8">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-6 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <AppLogoIcon class="size-3.5" />
                    </span>
                    <span class="font-medium text-foreground">KONEXUS</span>
                    <span class="text-muted-foreground/60">— School Management Information System</span>
                </div>
                <p>© {{ new Date().getFullYear() }} KONEXUS. Built for schools.</p>
            </div>
        </footer>
    </div>
</template>