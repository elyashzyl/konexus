<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import { portalApi } from '@/lib/portalApi';
import type { ChildSummary } from '@/types/platform';
import { useAuthStore } from '@/stores/auth';
import { ROLE_HOME_PATHS } from '@/lib/roles';
import { staffPortalByRole } from '@/config/staffPortals';
import {
    BookOpen,
    CalendarDays,
    ClipboardList,
    FolderLock,
    GraduationCap,
    LayoutGrid,
    Megaphone,
    ShieldCheck,
    Users,
    UserRound,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

type Props = {
    role: 'student' | 'parent' | 'teacher' | 'staff';
};

const props = defineProps<Props>();

const route = useRoute();
const auth = useAuthStore();
const { getInitials } = useInitials();
const { state } = useSidebar();
const isCollapsed = computed(() => state.value === 'collapsed');

const identity = ref<{ name: string; subline: string; roleLabel: string } | null>(null);
const children = ref<ChildSummary[]>([]);

onMounted(async () => {
    try {
        if (props.role === 'student') {
            const data = await portalApi.student.dashboard();
            const profile = data.profile;
            identity.value = {
                name: profile?.name ?? 'Learner account',
                subline: profile?.grade_level ? `${profile.grade_level} learner` : 'Learner account',
                roleLabel: 'Learner',
            };
        } else if (props.role === 'parent') {
            const data = await portalApi.parent.dashboard();
            children.value = data.children;
            identity.value = {
                name: data.parent?.name ?? 'Guardian account',
                subline: `${data.children.length} linked ${data.children.length === 1 ? 'child' : 'children'}`,
                roleLabel: 'Guardian',
            };
        } else if (props.role === 'teacher') {
            const data = await portalApi.teacher.dashboard();
            identity.value = {
                name: data.teacher.name,
                subline: data.teacher.advisory_section ? `Adviser · ${data.teacher.advisory_section}` : (data.teacher.department ?? 'Teaching staff'),
                roleLabel: 'Teacher',
            };
        } else {
            const role = auth.primaryRole;
            const portal = staffRole.value;
            const rolePath = role ? ROLE_HOME_PATHS[role.name] : undefined;
            identity.value = {
                name: auth.user?.name ?? 'Staff account',
                subline: portal?.label ?? role?.label ?? 'School staff',
                roleLabel: portal?.eyebrow ?? (role && rolePath ? role.name.replace(/-/g, ' ') : 'Staff'),
            };
        }
    } catch {
        identity.value = null;
    }
});

const staffRole = computed(() => (auth.primaryRole ? staffPortalByRole(auth.primaryRole.name) : undefined));

const overviewHref = computed(() => {
    if (props.role === 'student') return '/portal/student';
    if (props.role === 'parent') return '/portal/parent';
    if (props.role === 'teacher') return '/portal/teacher';

    const role = auth.primaryRole;
    return role && ROLE_HOME_PATHS[role.name] ? ROLE_HOME_PATHS[role.name] : '/portal/staff/principal';
});

function isActive(href: string): boolean {
    return route.path === href || route.path.startsWith(`${href}/`);
}

interface NavItem {
    title: string;
    href: string;
    icon: LucideIcon;
}

const studentGroups = computed<{ label: string; items: NavItem[] }[]>(() => [
    {
        label: 'My record',
        items: [
            { title: 'Grades', href: '/portal/student/grades', icon: BookOpen },
            { title: 'Weekly schedule', href: '/portal/student/schedule', icon: CalendarDays },
        ],
    },
    {
        label: 'Registrar',
        items: [
            { title: 'Enrollment history', href: '/portal/student/enrollments', icon: GraduationCap },
            { title: 'Private documents', href: '/portal/student/documents', icon: FolderLock },
        ],
    },
    {
        label: 'School life',
        items: [{ title: 'Announcements', href: '/portal/student/announcements', icon: Megaphone }],
    },
]);

const parentGroups = computed<{ label: string; items: NavItem[] }[]>(() => [
    {
        label: 'My family',
        items: children.value.length
            ? children.value.map((child) => ({ title: child.name, href: `/portal/parent/children/${child.id}`, icon: UserRound }))
            : [{ title: 'No linked children yet', href: '/portal/parent', icon: UserRound }],
    },
]);

const teacherGroups = computed<{ label: string; items: NavItem[] }[]>(() => [
    {
        label: 'Teaching load',
        items: [
            { title: 'My classes', href: '/portal/teacher/classes', icon: ClipboardList },
            { title: 'Weekly schedule', href: '/portal/teacher/schedule', icon: CalendarDays },
        ],
    },
    {
        label: 'Classroom',
        items: [{ title: 'Advisory class', href: '/portal/teacher/advisory', icon: Users }],
    },
]);

const staffGroups = computed<{ label: string; items: NavItem[] }[]>(() => [
    {
        label: staffRole.value?.eyebrow ?? 'Office',
        items: [
            { title: 'Overview', href: overviewHref.value, icon: LayoutGrid },
            { title: 'Announcements', href: `${overviewHref.value}/announcements`, icon: Megaphone },
        ],
    },
]);

const groups = computed(() => {
    if (props.role === 'student') return studentGroups.value;
    if (props.role === 'teacher') return teacherGroups.value;
    if (props.role === 'parent') return parentGroups.value;
    return staffGroups.value;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <RouterLink :to="overviewHref">
                            <AppLogo />
                        </RouterLink>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <div v-if="identity" class="px-2 pt-4 pb-1" :class="{ 'opacity-0': isCollapsed }">
                <div class="relative overflow-hidden rounded-2xl border border-sidebar-border bg-sidebar-accent/60 p-4">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent" />
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary font-semibold text-primary-foreground">
                            {{ getInitials(identity.name) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ identity.name }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ identity.subline }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 border-t border-sidebar-border pt-2.5 text-[11px] text-muted-foreground">
                        <ShieldCheck class="size-3.5 text-primary" />
                        Private {{ identity.roleLabel.toLowerCase() }} record
                    </div>
                </div>
            </div>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isActive(overviewHref)" :tooltip="'Overview'">
                            <RouterLink :to="overviewHref">
                                <LayoutGrid />
                                <span>Overview</span>
                            </RouterLink>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup v-for="section in groups" :key="section.label" class="px-2 py-0">
                <SidebarGroupLabel>{{ section.label }}</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in section.items" :key="item.title">
                        <SidebarMenuButton as-child :is-active="isActive(item.href)" :tooltip="item.title">
                            <RouterLink :to="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </RouterLink>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser :show-settings="false" />
        </SidebarFooter>
    </Sidebar>
</template>
