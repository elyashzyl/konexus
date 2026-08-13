<script setup lang="ts">
import PortalEmptyState from '@/components/portal/PortalEmptyState.vue';
import { extractError } from '@/lib/api';
import { portalApi } from '@/lib/portalApi';
import type { AnnouncementItem, ChildSummary } from '@/types/platform';
import {
    ArrowRight,
    BookOpen,
    CalendarDays,
    CheckCircle2,
    FileText,
    GraduationCap,
    HeartPulse,
    MapPin,
    Megaphone,
    ShieldCheck,
    Sparkles,
    UserRound,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const profile = ref<ChildSummary | null>(null);
const announcements = ref<AnnouncementItem[]>([]);

onMounted(async () => {
    try {
        const data = await portalApi.student.dashboard();
        profile.value = data.profile;
        announcements.value = data.announcements;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
});

const identifiers = computed(() => [
    { label: 'School ID', value: profile.value?.student_number || 'Not recorded' },
    { label: 'LRN', value: profile.value?.lrn || 'Not recorded' },
    { label: 'Grade level', value: profile.value?.grade_level || 'Not recorded' },
]);

const quickLinks = computed<{ title: string; description: string; href: string; icon: LucideIcon }[]>(() => [
    { title: 'Grades', description: 'Published grading records and report cards.', href: '/portal/student/grades', icon: BookOpen },
    { title: 'Weekly schedule', description: 'The timetable for the current term.', href: '/portal/student/schedule', icon: CalendarDays },
    { title: 'Enrollment history', description: 'Every school year on the official record.', href: '/portal/student/enrollments', icon: GraduationCap },
    { title: 'Private documents', description: 'Secure files released by the registrar.', href: '/portal/student/documents', icon: FileText },
    { title: 'Announcements', description: 'School news and official notices.', href: '/portal/student/announcements', icon: Megaphone },
]);

function formatDate(value?: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}

function pad(index: number): string {
    return String(index + 1).padStart(2, '0');
}
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pb-20 sm:px-8 lg:px-12">
            <div v-if="loading" class="space-y-4 pt-10">
                <div v-for="i in 3" :key="i" class="h-28 animate-pulse rounded-2xl bg-muted/60" />
            </div>

            <div v-else-if="!profile" class="pt-10">
                <PortalEmptyState
                    :icon="ShieldCheck"
                    title="Learner record not found"
                    description="No student record is linked to this account yet."
                />
            </div>

            <template v-else>
                <section class="portal-rise grid gap-12 pt-10 pb-14 lg:grid-cols-[1.25fr_0.75fr] lg:gap-16 lg:pt-16">
                    <div class="flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/8 text-primary ring-1 ring-primary/10">
                                    <GraduationCap class="size-4" />
                                </span>
                                <p class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase">Official learner record</p>
                            </div>

                            <h1 class="mt-8 font-display text-[3rem] leading-[1.02] font-medium tracking-[-0.025em] text-foreground sm:text-[4rem]">
                                {{ profile.name }}
                            </h1>
                            <p class="mt-5 text-[15px] leading-7 text-muted-foreground">
                                Welcome back. Your records are kept here as the registrar maintains them — private, current, and always available to you.
                            </p>
                        </div>

                        <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3">
                            <span class="inline-flex items-center gap-2 text-xs text-muted-foreground">
                                <ShieldCheck class="size-3.5 text-primary" /> Private student record
                            </span>
                            <span class="inline-flex items-center gap-2 text-xs text-muted-foreground">
                                <Sparkles class="size-3.5 text-primary" /> Registrar-sourced
                            </span>
                        </div>
                    </div>

                    <aside class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-border/60 bg-card p-7">
                        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/30 to-transparent" />
                        <div>
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase">Current standing</p>
                                <span class="size-1.5 rounded-full bg-primary" />
                            </div>

                            <div class="mt-6">
                                <p class="font-display text-4xl font-medium tracking-[-0.02em] text-foreground">
                                    {{ profile.grade_level || '—' }}
                                </p>
                                <p class="mt-1.5 text-sm text-muted-foreground">{{ profile.academic_year || '—' }}</p>
                            </div>

                            <div class="mt-7 space-y-3.5 border-t border-border/60 pt-5 text-sm">
                                <div v-if="profile.section" class="flex items-start gap-3">
                                    <GraduationCap class="mt-0.5 size-4 shrink-0 text-primary" />
                                    <span>{{ profile.section }}</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <UserRound class="mt-0.5 size-4 shrink-0 text-primary" />
                                    <span>Adviser: {{ profile.adviser || '—' }}</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-primary" />
                                    <span>{{ profile.enrollment_status_label || 'No active enrollment' }}</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <MapPin class="mt-0.5 size-4 shrink-0 text-primary" />
                                    <span>{{ profile.campus || '—' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center gap-3 border-t border-border/60 pt-4 text-sm">
                            <div class="flex-1">
                                <p class="text-[10px] font-medium tracking-[0.2em] text-muted-foreground/70 uppercase">General average</p>
                                <p class="mt-1 font-display text-2xl font-medium tracking-[-0.01em] text-foreground">
                                    {{ profile.academic_summary.general_average ?? '—' }}
                                </p>
                            </div>
                            <p class="font-mono text-[10px] tracking-[0.18em] text-muted-foreground/70 uppercase">Ver {{ String(profile.id).padStart(5, '0') }}</p>
                        </div>
                    </aside>
                </section>

                <section class="portal-rise overflow-hidden rounded-2xl border border-border/60 bg-card" style="animation-delay: 80ms">
                    <div class="grid divide-y divide-border/60 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                        <div v-for="(item, index) in identifiers" :key="item.label" class="flex items-center gap-4 px-6 py-5">
                            <span class="index-num font-mono text-xs text-muted-foreground/60">{{ pad(index) }}</span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-medium tracking-[0.2em] text-muted-foreground/70 uppercase">{{ item.label }}</p>
                                <p class="mt-1 truncate font-mono text-sm font-medium text-foreground">{{ item.value }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-if="quickLinks.length" class="portal-rise mt-16" style="animation-delay: 160ms">
                    <div class="flex items-end justify-between gap-6">
                        <div>
                            <p class="text-[11px] font-medium tracking-[0.22em] text-primary uppercase">Contents</p>
                            <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">Your portal</h2>
                        </div>
                        <p class="hidden font-mono text-[11px] tracking-[0.18em] text-muted-foreground/70 uppercase sm:block">
                            {{ quickLinks.length }} sections
                        </p>
                    </div>

                    <div class="mt-8 grid gap-x-10 gap-y-0 sm:grid-cols-2">
                        <RouterLink
                            v-for="(link, index) in quickLinks"
                            :key="link.href"
                            :to="link.href"
                            class="group flex items-center gap-5 border-b border-border/60 py-5"
                        >
                            <span class="index-num w-7 shrink-0 font-mono text-xs text-muted-foreground/60 transition group-hover:text-primary">
                                {{ pad(index) }}
                            </span>
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/6 text-primary ring-1 ring-primary/10 transition group-hover:bg-primary group-hover:text-primary-foreground">
                                <component :is="link.icon" class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-display text-lg font-medium tracking-[-0.01em] text-foreground">{{ link.title }}</span>
                                <span class="mt-0.5 block truncate text-[13px] text-muted-foreground">{{ link.description }}</span>
                            </span>
                            <ArrowRight class="size-4 shrink-0 text-muted-foreground/40 transition group-hover:translate-x-0.5 group-hover:text-primary" />
                        </RouterLink>
                    </div>
                </section>

                <section v-if="announcements.length" class="portal-rise mt-16" style="animation-delay: 240ms">
                    <div class="flex items-baseline justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-medium tracking-[0.22em] text-muted-foreground uppercase">School life</p>
                            <h3 class="mt-2 font-display text-2xl font-medium tracking-[-0.01em] text-foreground">Announcements</h3>
                        </div>
                        <RouterLink
                            to="/portal/student/announcements"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                        >
                            View all
                            <ArrowRight class="size-3.5" />
                        </RouterLink>
                    </div>

                    <div class="mt-6 divide-y divide-border/60 border-y border-border/60">
                        <RouterLink
                            v-for="(announcement, index) in announcements.slice(0, 3)"
                            :key="announcement.id"
                            to="/portal/student/announcements"
                            class="group flex items-center gap-4 py-4"
                        >
                            <span class="index-num w-7 shrink-0 font-mono text-xs text-muted-foreground/60">{{ pad(index) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[15px] font-medium text-foreground group-hover:underline">{{ announcement.title }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ formatDate(announcement.published_at) }}</p>
                            </div>
                            <ArrowRight class="size-4 shrink-0 text-muted-foreground/40 transition group-hover:translate-x-0.5 group-hover:text-primary" />
                        </RouterLink>
                    </div>
                </section>

                <footer class="portal-rise mt-20 border-t border-border/60 pt-6" style="animation-delay: 320ms">
                    <div class="flex flex-col justify-between gap-3 text-xs text-muted-foreground sm:flex-row sm:items-center">
                        <p>Protect your account and do not share your password.</p>
                        <div class="flex items-center gap-2">
                            <HeartPulse class="size-3.5 text-primary" />
                            Records are maintained by the registrar.
                        </div>
                    </div>
                </footer>
            </template>
        </div>
    </div>
</template>
