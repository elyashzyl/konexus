<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { extractError, extractFieldErrors } from '@/lib/api';
import { platformApi, type AdminUser, type UserInput } from '@/lib/platformApi';
import { homePathForRoles } from '@/lib/roles';
import { useAuthStore } from '@/stores/auth';
import { Eye, KeyRound, Pencil, Plus, UserCog, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { toast } from 'vue-sonner';

const auth = useAuthStore();
const router = useRouter();

const loading = ref(true);
const saving = ref(false);
const items = ref<AdminUser[]>([]);
const total = ref(0);
const page = ref(1);
const perPage = 15;
const search = ref('');
const roleFilter = ref<'all' | string>('all');
const roleOptions = ref<{ name: string; label: string }[]>([]);
const fieldErrors = ref<Record<string, string[]>>({});

const dialogOpen = ref(false);
const editing = ref<AdminUser | null>(null);
const form = ref<UserInput>({ name: '', email: '', password: '', is_active: true, roles: [] });

const resetDialogOpen = ref(false);
const resetPasswordValue = ref('');

onMounted(async () => {
    try {
        roleOptions.value = await platformApi.users.roleOptions();
    } catch (error) {
        toast.error(extractError(error));
    }
    await refresh();
});

async function refresh(): Promise<void> {
    loading.value = true;
    try {
        const data = await platformApi.users.index({
            page: page.value,
            per_page: perPage,
            search: search.value || undefined,
            role: roleFilter.value === 'all' ? undefined : roleFilter.value,
        });
        items.value = data.items;
        total.value = data.pagination.total;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
}

function openCreate(): void {
    editing.value = null;
    form.value = { name: '', email: '', password: '', is_active: true, roles: [] };
    fieldErrors.value = {};
    dialogOpen.value = true;
}

function openEdit(user: AdminUser): void {
    editing.value = user;
    form.value = {
        name: user.name,
        email: user.email,
        password: '',
        is_active: user.is_active,
        roles: user.roles.map((role) => role.name),
    };
    fieldErrors.value = {};
    dialogOpen.value = true;
}

async function save(): Promise<void> {
    saving.value = true;
    fieldErrors.value = {};
    try {
        const payload: UserInput = { ...form.value, roles: form.value.roles ?? [] };
        if (!payload.password) {
            delete payload.password;
        }

        if (editing.value) {
            const updated = await platformApi.users.update(editing.value.id, payload);
            toast.success(`User "${updated.name}" updated.`);
        } else {
            const created = await platformApi.users.store(payload);
            toast.success(`User "${created.name}" created.`);
        }
        dialogOpen.value = false;
        await refresh();
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        toast.error(extractError(error));
    } finally {
        saving.value = false;
    }
}

async function toggleActive(user: AdminUser): Promise<void> {
    try {
        await platformApi.users.toggleActive(user.id);
        toast.success(user.is_active ? 'User deactivated.' : 'User activated.');
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    }
}

async function requestReset(user: AdminUser): Promise<void> {
    editing.value = user;
    resetPasswordValue.value = '';
    resetDialogOpen.value = true;
}

async function confirmReset(): Promise<void> {
    if (!editing.value) return;
    try {
        await platformApi.users.resetPassword(editing.value.id, resetPasswordValue.value);
        toast.success('Password reset.');
        resetDialogOpen.value = false;
    } catch (error) {
        toast.error(extractError(error));
    }
}

async function remove(user: AdminUser): Promise<void> {
    if (!window.confirm(`Delete user "${user.name}"? This cannot be undone.`)) return;
    try {
        await platformApi.users.destroy(user.id);
        toast.success('User deleted.');
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    }
}

async function impersonate(user: AdminUser): Promise<void> {
    if (!window.confirm(`Impersonate "${user.name}"? You will act as this user until you stop impersonating.`)) return;
    try {
        const target = await auth.impersonate(user.id);
        toast.success(`Now acting as ${target.name}.`);
        await router.push(homePathForRoles(target.roles));
    } catch (error) {
        toast.error(extractError(error));
    }
}

const canImpersonate = computed(() => (user: AdminUser) =>
    user.is_active &&
    user.id !== auth.user?.id &&
    !user.roles.some((role) => role.name === 'super-administrator' || role.name === 'school-administrator'),
);

const lastPage = computed(() => Math.max(1, Math.ceil(total.value / perPage)));
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]" />

        <div class="relative w-full px-5 pt-10 pb-20 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="UserCog"
                index="02"
                eyebrow="Accounts"
                title="Users & Roles"
                description="Manage accounts, roles and access across the school."
            >
                <template #actions>
                    <Button @click="openCreate"><Plus class="size-4" /> New user</Button>
                </template>
            </AdminPageHeader>

            <section class="portal-rise mt-10">
                <div class="flex flex-wrap items-center gap-2">
                    <Input v-model="search" placeholder="Search users…" class="w-56" @keydown.enter="page = 1; refresh()" />
                    <Select :model-value="roleFilter" @update:model-value="(v: string) => { roleFilter = v; page = 1; refresh(); }">
                        <SelectTrigger class="w-44">
                            <SelectValue :placeholder="roleFilter === 'all' ? 'All roles' : roleFilter" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All roles</SelectItem>
                            <SelectItem v-for="role in roleOptions" :key="role.name" :value="role.name">{{ role.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button variant="ghost" size="sm" @click="page = 1; refresh()">Apply</Button>
                    <div class="ml-auto text-sm text-muted-foreground">{{ total }} users</div>
                </div>
            </section>

            <section class="portal-rise mt-6" style="animation-delay: 120ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader>
                        <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">Accounts</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="loading" class="space-y-2">
                            <Skeleton v-for="i in 6" :key="i" class="h-12" />
                        </div>

                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Roles</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Last login</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in items" :key="user.id">
                                    <TableCell>
                                        <div class="font-medium">{{ user.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1">
                                            <Badge v-for="role in user.roles" :key="role.id" variant="secondary">{{ role.label }}</Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Switch :model-value="user.is_active" @update:model-value="toggleActive(user)" aria-label="Toggle active" />
                                    </TableCell>
                                    <TableCell class="text-xs text-muted-foreground">
                                        {{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never' }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button v-if="canImpersonate(user)" variant="ghost" size="icon" aria-label="Impersonate" @click="impersonate(user)">
                                                <Eye class="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" aria-label="Edit" @click="openEdit(user)">
                                                <Pencil class="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" aria-label="Reset password" @click="requestReset(user)">
                                                <KeyRound class="size-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" aria-label="Delete" class="text-destructive" @click="remove(user)">
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div v-if="total > perPage" class="mt-4 flex items-center justify-end gap-2">
                            <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--; refresh()">Previous</Button>
                            <span class="text-sm text-muted-foreground">Page {{ page }} of {{ lastPage }}</span>
                            <Button variant="outline" size="sm" :disabled="page >= lastPage" @click="page++; refresh()">Next</Button>
                        </div>
                    </CardContent>
                </Card>
            </section>

        <Dialog v-model:open="dialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editing ? `Edit ${editing.name}` : 'New user' }}</DialogTitle>
                    <DialogDescription>Set the account details and assign roles.</DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="save">
                    <div class="space-y-2">
                        <Label for="user-name">Name</Label>
                        <Input id="user-name" v-model="form.name" placeholder="Full name" />
                        <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name[0] }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="user-email">Email</Label>
                        <Input id="user-email" v-model="form.email" type="email" placeholder="name@example.com" />
                        <p v-if="fieldErrors.email" class="text-xs text-destructive">{{ fieldErrors.email[0] }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="user-password">{{ editing ? 'New password (optional)' : 'Password' }}</Label>
                        <Input id="user-password" v-model="form.password" type="password" placeholder="At least 8 characters" />
                        <p v-if="fieldErrors.password" class="text-xs text-destructive">{{ fieldErrors.password[0] }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label>Roles</Label>
                        <div v-if="roleOptions.length > 0" class="grid gap-2 sm:grid-cols-2">
                            <label v-for="role in roleOptions" :key="role.name" class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                <Checkbox
                                    :model-value="form.roles?.includes(role.name)"
                                    @update:model-value="(checked: boolean) => {
                                        if (!form.roles) form.roles = [];
                                        if (checked) form.roles.push(role.name);
                                        else form.roles = form.roles.filter((name) => name !== role.name);
                                    }"
                                />
                                {{ role.label }}
                            </label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="resetDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reset password</DialogTitle>
                    <DialogDescription>Set a new password for {{ editing?.name }}.</DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="confirmReset">
                    <div class="space-y-2">
                        <Label for="reset-password">New password</Label>
                        <Input id="reset-password" v-model="resetPasswordValue" type="password" placeholder="At least 8 characters" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="resetDialogOpen = false">Cancel</Button>
                        <Button type="submit">Reset</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        </div>
    </div>
</template>
