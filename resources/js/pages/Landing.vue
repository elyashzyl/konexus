<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import SocialAuthButtons from '@/components/SocialAuthButtons.vue';
import { Button } from '@/components/ui/button';
import { APP_ROUTES, AUTH_ROUTES } from '@/constants/app';
import api from '@/lib/api';
import {
    ArrowRight,
    BarChart3,
    BookOpen,
    Boxes,
    Building2,
    Check,
    ClipboardCheck,
    GraduationCap,
    HeartPulse,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

interface PublicPlan {
    id: number;
    name: string;
    code: string;
    description: string | null;
    billing_cycle: string;
    monthly_price: number;
    annual_price: number;
    trial_days: number | null;
    max_students: number | null;
    max_storage_mb: number | null;
    display_order: number;
    features: { code: string; label: string }[];
}

const plans = ref<PublicPlan[]>([]);
const plansLoading = ref(true);

onMounted(async () => {
    try {
        const response = await api.get<{ data: PublicPlan[] }>('/public/plans');
        plans.value = response.data.data;
    } catch {
        plans.value = [];
    } finally {
        plansLoading.value = false;
    }
});

const navLinks: { label: string; href?: string; to?: string }[] = [
    { label: 'Modules', href: '#modules' },
    { label: 'Portals', href: '#portals' },
    { label: 'Enrollment', to: APP_ROUTES.enrollment.path },
    { label: 'Why KONEXUS', href: '#why' },
    { label: 'Pricing', href: '#pricing' },
];

const modules = [
    {
        icon: Building2,
        title: 'School foundation',
        items: ['School profile', 'Campuses & buildings', 'Rooms & departments', 'Grade levels & sections', 'Academic years & terms', 'Subjects'],
    },
    {
        icon: Users,
        title: 'People & roles',
        items: ['Students', 'Parents & guardians', 'Teachers', 'Staff', 'One record per person'],
    },
    {
        icon: ClipboardCheck,
        title: 'Enrollment',
        items: ['Requirements & documents', 'Capacity & overrides', 'Transfers', 'Digital signatures', 'Enrollment numbers'],
    },
    {
        icon: BookOpen,
        title: 'Academic management',
        items: ['Curriculum', 'Subject offerings', 'Class rosters', 'Schedules with conflict checks', 'Grade scales & records'],
    },
    {
        icon: Boxes,
        title: 'School operations',
        items: ['Attendance', 'Finance & billing', 'Library', 'Clinic & guidance', 'Inventory & assets'],
    },
    {
        icon: BarChart3,
        title: 'Reports & activity',
        items: ['Operational reports', 'Live dashboard', 'Full activity trail', 'Grade corrections, tracked'],
    },
];

const portals = [
    {
        icon: GraduationCap,
        title: 'Student portal',
        description: 'Grades, schedule, attendance and announcements in one quiet place.',
    },
    {
        icon: HeartPulse,
        title: 'Parent portal',
        description: 'Follow progress, notices and records without the paper trail.',
    },
    {
        icon: BookOpen,
        title: 'Teacher portal',
        description: 'Classes, rosters, schedules and grade entry at the ready.',
    },
    {
        icon: ShieldCheck,
        title: 'Administration',
        description: 'Users, school records, permissions and analytics under one roof.',
    },
];

const pillars = [
    {
        index: '01',
        title: 'Structure',
        description: 'One linked model holds the campus, the calendar and the people — so a record entered once is the same record everywhere.',
    },
    {
        index: '02',
        title: 'Rhythm',
        description: 'Years, terms and schedules keep enrollment, grading and reporting in step with the way a school actually runs.',
    },
    {
        index: '03',
        title: 'Trust',
        description: 'Every role sees only what it needs, and every change leaves a trail — so the office always knows what happened and when.',
    },
];

const mockStats = [
    { label: 'Enrolled', value: '1,284' },
    { label: 'Sections', value: '42' },
    { label: 'Teachers', value: '36' },
];

const mockSchedule = [
    { time: '07:45', subject: 'Science 9', section: '9 — Diamond', room: 'Lab 2' },
    { time: '08:40', subject: 'Mathematics 7', section: '7 — Emerald', room: 'Room 104' },
    { time: '09:35', subject: 'Filipino 10', section: '10 — Ruby', room: 'Room 203' },
    { time: '10:30', subject: 'English 8', section: '8 — Amber', room: 'Room 118' },
];

const mockSidebar = ['Overview', 'Enrollment', 'Schedules', 'Grades', 'Reports', 'Settings'];
</script>

<template>
    <div class="relative min-h-svh overflow-x-clip bg-background">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-[46rem] bg-[radial-gradient(60rem_28rem_at_50%_-24%,hsl(26_57%_40%/0.12),transparent)]"
        />

        <!-- Navigation -->
        <header class="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8">
                <RouterLink :to="APP_ROUTES.landing.path" class="flex items-center gap-2.5">
                    <AppLogoIcon class="size-8 rounded-md" />
                    <span
                        class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)] bg-clip-text font-display text-lg font-semibold tracking-[-0.01em] text-transparent"
                    >
                        KONEXUS
                    </span>
                </RouterLink>

                <nav class="hidden items-center gap-8 md:flex">
                    <template v-for="link in navLinks" :key="link.label">
                        <RouterLink
                            v-if="link.to"
                            :to="link.to"
                            class="text-[13px] font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            {{ link.label }}
                        </RouterLink>
                        <a
                            v-else
                            :href="link.href"
                            class="text-[13px] font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            {{ link.label }}
                        </a>
                    </template>
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
        <section class="relative mx-auto max-w-6xl px-5 pb-16 pt-20 sm:px-8 sm:pt-28">
            <div class="portal-rise mx-auto max-w-3xl text-center">
                <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">School Management Information System</p>
                <h1 class="mt-6 font-display text-5xl font-medium leading-[1.02] tracking-[-0.02em] text-foreground sm:text-7xl">
                    Every record, every role,<br />
                    one calm system.
                </h1>
                <p class="mx-auto mt-6 max-w-xl text-[15px] leading-7 text-muted-foreground">
                    KONEXUS brings the people, records and rhythm of a school into a single workspace — enrollment, classes, schedules and grades —
                    with a portal for each role and a trail for every change.
                </p>
                <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                    <Button size="lg" class="gap-2" as-child>
                        <RouterLink :to="APP_ROUTES.enrollment.path">
                            Start enrollment
                            <ArrowRight class="size-4" />
                        </RouterLink>
                    </Button>
                    <Button size="lg" variant="outline" as-child>
                        <RouterLink :to="AUTH_ROUTES.register.path">Explore the modules</RouterLink>
                    </Button>
                    <Button size="lg" variant="ghost" as-child>
                        <RouterLink :to="AUTH_ROUTES.login.path">Sign in to your portal</RouterLink>
                    </Button>
                </div>
                <div class="mx-auto mt-8 max-w-md">
                    <div class="flex items-center gap-3">
                        <span class="h-px flex-1 bg-border/70" />
                        <span class="text-xs uppercase tracking-wider text-muted-foreground">or enroll with</span>
                        <span class="h-px flex-1 bg-border/70" />
                    </div>
                    <SocialAuthButtons class="mt-4" intended="/enrollment" label="Enroll with" />
                </div>
            </div>

            <!-- Product console mock -->
            <div class="portal-rise mt-20" style="animation-delay: 140ms">
                <div class="relative overflow-hidden rounded-2xl border border-border/60 bg-card/60 shadow-[0_24px_60px_-24px_hsl(26_57%_40%/0.25)]">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />

                    <div class="flex items-center justify-between border-b border-border/60 px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="flex gap-1.5">
                                <span class="size-2.5 rounded-full bg-border" />
                                <span class="size-2.5 rounded-full bg-border" />
                                <span class="size-2.5 rounded-full bg-border" />
                            </div>
                            <span class="text-xs text-muted-foreground">konexus — office console</span>
                        </div>
                        <span class="hidden font-mono text-[11px] text-muted-foreground/70 sm:block">SY 2026-2027 · Q1</span>
                    </div>

                    <div class="grid md:grid-cols-[16rem_1fr]">
                        <aside class="hidden border-r border-border/60 p-4 md:block">
                            <p class="px-3 pb-3 text-[10px] font-medium uppercase tracking-[0.18em] text-muted-foreground/60">Workspace</p>
                            <div class="space-y-1">
                                <div
                                    v-for="item in mockSidebar"
                                    :key="item"
                                    class="rounded-lg px-3 py-2 text-[13px]"
                                    :class="item === 'Overview' ? 'bg-primary/10 font-medium text-primary' : 'text-muted-foreground'"
                                >
                                    {{ item }}
                                </div>
                            </div>
                        </aside>

                        <div class="p-5 sm:p-6">
                            <div class="grid grid-cols-3 gap-3">
                                <div v-for="stat in mockStats" :key="stat.label" class="rounded-xl border border-border/60 bg-card p-4">
                                    <p class="index-num font-mono text-2xl font-medium text-foreground sm:text-3xl">{{ stat.value }}</p>
                                    <p class="mt-1 text-[11px] text-muted-foreground">{{ stat.label }}</p>
                                </div>
                            </div>

                            <div class="mt-5 rounded-xl border border-border/60 bg-card p-4">
                                <div class="flex items-center justify-between pb-3">
                                    <p class="text-[13px] font-medium text-foreground">Today's schedule</p>
                                    <span class="text-[11px] text-muted-foreground">8 periods</span>
                                </div>
                                <div class="divide-y divide-border/60">
                                    <div v-for="row in mockSchedule" :key="row.time" class="flex items-center justify-between gap-4 py-2.5">
                                        <div class="flex items-center gap-4">
                                            <span class="index-num w-12 font-mono text-xs text-primary">{{ row.time }}</span>
                                            <div>
                                                <p class="text-[13px] font-medium text-foreground">{{ row.subject }}</p>
                                                <p class="text-[11px] text-muted-foreground">{{ row.section }}</p>
                                            </div>
                                        </div>
                                        <span class="hidden text-[11px] text-muted-foreground sm:block">{{ row.room }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modules -->
        <section id="modules" class="bg-sidebar-background relative border-y border-border/60">
            <div class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
                <div class="portal-rise">
                    <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">No. 01 — The modules</p>
                    <h2 class="mt-4 font-display text-4xl font-medium tracking-[-0.02em] text-foreground sm:text-5xl">School records, structured</h2>
                    <p class="mt-5 max-w-xl text-[15px] leading-7 text-muted-foreground">
                        From the campus map to the grade book, every module shares one record model — so nothing gets lost between departments.
                    </p>
                </div>

                <div class="portal-rise mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" style="animation-delay: 120ms">
                    <div
                        v-for="(module, index) in modules"
                        :key="module.title"
                        class="group relative overflow-hidden rounded-2xl border border-border/60 bg-card/60 p-6 transition-colors hover:border-primary/25"
                    >
                        <div
                            class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent"
                        />
                        <div class="flex items-center justify-between">
                            <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                            <span class="bg-primary/8 flex size-9 items-center justify-center rounded-lg text-primary ring-1 ring-primary/10">
                                <component :is="module.icon" class="size-4" />
                            </span>
                        </div>
                        <h3 class="mt-5 font-display text-xl font-medium tracking-[-0.01em] text-foreground">{{ module.title }}</h3>
                        <ul class="mt-3 space-y-1.5">
                            <li v-for="item in module.items" :key="item" class="flex items-center gap-2 text-sm leading-6 text-muted-foreground">
                                <span class="size-1 rounded-full bg-primary/50" />
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Portals -->
        <section id="portals" class="relative mx-auto max-w-6xl px-5 py-20 sm:px-8">
            <div class="portal-rise">
                <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">No. 02 — The portals</p>
                <h2 class="mt-4 font-display text-4xl font-medium tracking-[-0.02em] text-foreground sm:text-5xl">A workspace for every role</h2>
                <p class="mt-5 max-w-xl text-[15px] leading-7 text-muted-foreground">
                    Students, parents, teachers and the school office each see only what their role needs — one sign-in, the right view.
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

        <!-- Why KONEXUS -->
        <section id="why" class="bg-sidebar-background relative border-y border-border/60">
            <div class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
                <div class="portal-rise">
                    <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">No. 03 — Why KONEXUS</p>
                    <h2 class="mt-4 font-display text-4xl font-medium tracking-[-0.02em] text-foreground sm:text-5xl">
                        Built the way a school works
                    </h2>
                </div>
                <div class="portal-rise mt-10 space-y-0" style="animation-delay: 120ms">
                    <div
                        v-for="pillar in pillars"
                        :key="pillar.index"
                        class="grid gap-4 border-t border-border/60 py-8 sm:grid-cols-[6rem_1fr_2fr] sm:gap-8"
                    >
                        <span class="index-num font-mono text-sm text-primary">{{ pillar.index }}</span>
                        <h3 class="font-display text-2xl font-medium tracking-[-0.01em] text-foreground">{{ pillar.title }}</h3>
                        <p class="text-sm leading-7 text-muted-foreground">{{ pillar.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing -->
        <section id="pricing" class="relative mx-auto max-w-6xl px-5 py-20 sm:px-8">
            <div class="portal-rise">
                <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">No. 04 — Pricing</p>
                <h2 class="mt-4 font-display text-4xl font-medium tracking-[-0.02em] text-foreground sm:text-5xl">
                    One platform, plans that grow with the school
                </h2>
                <p class="mt-5 max-w-xl text-[15px] leading-7 text-muted-foreground">
                    Start on a trial and move up as enrollment grows. Every plan includes the portals, the audit trail and the module features that
                    matter most.
                </p>
            </div>

            <div v-if="plansLoading" class="portal-rise mt-10 grid gap-4 lg:grid-cols-3" style="animation-delay: 120ms">
                <div v-for="i in 3" :key="i" class="h-80 animate-pulse rounded-2xl border border-border/60 bg-card/60" />
            </div>

            <div v-else-if="plans.length" class="portal-rise mt-10 grid gap-4 lg:grid-cols-3" style="animation-delay: 120ms">
                <div
                    v-for="(plan, index) in plans"
                    :key="plan.id"
                    class="relative flex flex-col overflow-hidden rounded-2xl border bg-card/60 p-6 transition-colors"
                    :class="
                        index === Math.floor(plans.length / 2)
                            ? 'border-primary/40 shadow-[0_24px_60px_-24px_hsl(26_57%_40%/0.25)]'
                            : 'border-border/60 hover:border-primary/25'
                    "
                >
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <div class="flex items-center justify-between">
                        <h3 class="font-display text-xl font-medium tracking-[-0.01em] text-foreground">{{ plan.name }}</h3>
                        <span
                            v-if="index === Math.floor(plans.length / 2)"
                            class="flex items-center gap-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-medium text-primary ring-1 ring-primary/15"
                        >
                            <Sparkles class="size-3" />
                            Most popular
                        </span>
                    </div>
                    <p v-if="plan.description" class="mt-2 text-sm leading-6 text-muted-foreground">{{ plan.description }}</p>

                    <div class="mt-5 flex items-baseline gap-1">
                        <span class="index-num font-display text-4xl font-medium tracking-[-0.02em] text-foreground">
                            ₱{{ plan.monthly_price.toLocaleString() }}
                        </span>
                        <span class="text-sm text-muted-foreground">/ month</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        ₱{{ plan.annual_price.toLocaleString() }}/year · {{ plan.trial_days ? `${plan.trial_days}-day free trial` : 'No trial' }}
                    </p>

                    <ul class="mt-6 space-y-2.5">
                        <li
                            v-for="feature in plan.features"
                            :key="feature.code"
                            class="flex items-start gap-2.5 text-sm leading-6 text-muted-foreground"
                        >
                            <span class="mt-1 flex size-4 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                <Check class="size-2.5 text-primary" />
                            </span>
                            {{ feature.label }}
                        </li>
                    </ul>

                    <div class="mt-auto pt-8">
                        <Button class="w-full" :variant="index === Math.floor(plans.length / 2) ? 'default' : 'outline'" as-child>
                            <RouterLink :to="AUTH_ROUTES.register.path">Start with {{ plan.name }}</RouterLink>
                        </Button>
                    </div>
                </div>
            </div>

            <p v-else class="portal-rise mt-10 text-center text-sm text-muted-foreground" style="animation-delay: 120ms">
                Plans are being prepared. Contact us to get your school set up.
            </p>
        </section>

        <!-- CTA -->
        <section class="relative mx-auto max-w-6xl px-5 py-20 sm:px-8">
            <div class="portal-rise relative overflow-hidden rounded-2xl border border-border/60 bg-card/60 px-6 py-16 text-center sm:px-12">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Ready when you are</p>
                <h2 class="mx-auto mt-4 max-w-xl font-display text-4xl font-medium tracking-[-0.02em] text-foreground sm:text-5xl">
                    Put the school office in order.
                </h2>
                <p class="mx-auto mt-5 max-w-md text-[15px] leading-7 text-muted-foreground">
                    Join KONEXUS and give every record, schedule and grade a single home.
                </p>
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
            <div
                class="mx-auto flex max-w-6xl flex-col justify-between gap-4 px-5 py-8 text-xs text-muted-foreground sm:flex-row sm:items-center sm:px-8"
            >
                <div class="flex items-center gap-2.5">
                    <AppLogoIcon class="size-6 rounded-md" />
                    <span
                        class="bg-gradient-to-r from-[#32483c] to-[hsl(26_57%_40%)] bg-clip-text font-display font-semibold tracking-[-0.01em] text-transparent"
                    >
                        KONEXUS
                    </span>
                    <span class="text-muted-foreground/60">— School Management Information System</span>
                </div>
                <p>© {{ new Date().getFullYear() }} KONEXUS. Built for schools.</p>
            </div>
        </footer>
    </div>
</template>
