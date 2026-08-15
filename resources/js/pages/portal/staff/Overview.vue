<script setup lang="ts">
import { staffPortalByRole } from '@/config/staffPortals';
import { useAuthStore } from '@/stores/auth';
import { ArrowUpRight, BadgeCheck, BriefcaseBusiness, HeartPulse, Megaphone, ShieldCheck, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

const auth = useAuthStore();
const route = useRoute();

const portal = computed(() => {
    const roleParam = route.params.role as string | undefined;

    return staffPortalByRole(roleParam ?? auth.primaryRole?.name ?? 'principal');
});

const roleLabel = computed(() => portal.value?.label ?? 'School staff');

function moduleHref(key: string): string | null {
    if (key === 'announcements') {
        return `${route.path}/announcements`;
    }

    if (key === 'enrollment-operations') {
        return `${route.path}/enrollment-operations`;
    }

    if (key === 'online-enrollment') {
        return '/enrollment';
    }

    return null;
}
</script>

<template>
    <div class="relative">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 sm:px-8 lg:px-12">
            <section class="portal-rise grid gap-12 pb-14 pt-10 lg:grid-cols-[1.25fr_0.75fr] lg:gap-16 lg:pt-16">
                <div class="flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="bg-primary/8 flex size-9 items-center justify-center rounded-lg text-primary ring-1 ring-primary/10">
                                <BriefcaseBusiness class="size-4" />
                            </span>
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">{{ portal?.eyebrow }}</p>
                        </div>

                        <h1 class="mt-8 font-display text-[3rem] font-medium leading-[1.02] tracking-[-0.025em] text-foreground sm:text-[4rem]">
                            {{ auth.user?.name }}
                        </h1>
                        <p class="mt-5 max-w-xl text-[15px] leading-7 text-muted-foreground">
                            {{ portal?.intro }}
                        </p>
                    </div>

                    <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3">
                        <span class="inline-flex items-center gap-2 text-xs text-muted-foreground">
                            <ShieldCheck class="size-3.5 text-primary" /> Private staff record
                        </span>
                        <span class="inline-flex items-center gap-2 text-xs text-muted-foreground">
                            <Sparkles class="size-3.5 text-primary" /> Office-sourced
                        </span>
                    </div>
                </div>

                <aside class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-border/60 bg-card p-7">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/30 to-transparent" />
                    <div>
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground">Your role</p>
                            <span class="size-1.5 rounded-full bg-primary" />
                        </div>

                        <div class="mt-6">
                            <p class="font-display text-2xl font-medium tracking-[-0.01em] text-foreground">{{ roleLabel }}</p>
                            <p class="mt-1.5 text-sm text-muted-foreground">{{ portal?.description }}</p>
                        </div>

                        <div class="mt-7 space-y-3.5 border-t border-border/60 pt-5 text-sm">
                            <div class="flex items-start gap-3">
                                <BadgeCheck class="mt-0.5 size-4 shrink-0 text-primary" />
                                <span>{{ auth.user?.email }}</span>
                            </div>
                        </div>
                    </div>

                    <p class="mt-8 border-t border-border/60 pt-4 font-mono text-[10px] uppercase tracking-[0.18em] text-muted-foreground/70">
                        {{ portal?.eyebrow }} · Staff portal
                    </p>
                </aside>
            </section>

            <section class="portal-rise mt-8" style="animation-delay: 160ms">
                <div class="flex items-end justify-between gap-6">
                    <div>
                        <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-primary">Workspace</p>
                        <h2 class="mt-3 font-display text-3xl font-medium tracking-[-0.015em] text-foreground">
                            In your {{ roleLabel.toLowerCase() }} workspace
                        </h2>
                    </div>
                </div>

                <div class="portal-rise mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(module, index) in portal?.modules ?? []"
                        :key="module.key"
                        class="group flex flex-col justify-between gap-8 rounded-2xl border border-border/60 bg-card/50 p-6 transition-colors hover:border-primary/25"
                    >
                        <div>
                            <span class="index-num font-mono text-xs text-muted-foreground/60">{{ String(index + 1).padStart(2, '0') }}</span>
                            <h3 class="mt-4 font-display text-lg font-medium tracking-[-0.01em] text-foreground">{{ module.title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">{{ module.description }}</p>
                        </div>

                        <RouterLink
                            v-if="moduleHref(module.key)"
                            :to="moduleHref(module.key)!"
                            class="mt-6 inline-flex w-fit items-center gap-1.5 text-xs font-medium uppercase tracking-[0.14em] text-primary transition-opacity group-hover:opacity-80"
                        >
                            Open
                            <ArrowUpRight class="size-3.5" />
                        </RouterLink>
                        <div v-else class="mt-6 flex items-center gap-2 text-xs text-muted-foreground/70">
                            <Megaphone class="size-3.5 text-primary/60" />
                            Managed by the school office
                        </div>
                    </div>
                </div>
            </section>

            <footer class="portal-rise mt-20 border-t border-border/60 pt-6" style="animation-delay: 240ms">
                <div class="flex flex-col justify-between gap-3 text-xs text-muted-foreground sm:flex-row sm:items-center">
                    <p>Protect your account and do not share your password.</p>
                    <div class="flex items-center gap-2">
                        <HeartPulse class="size-3.5 text-primary" />
                        Records are maintained by the school office.
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>
