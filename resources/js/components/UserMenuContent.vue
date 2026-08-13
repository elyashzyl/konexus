<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { APP_ROUTES, AUTH_ROUTES } from '@/constants/app';
import { useAuthStore } from '@/stores/auth';
import type { User } from '@/types';
import { LogOut, Settings } from 'lucide-vue-next';
import { useRouter } from 'vue-router';

interface Props {
    user: User;
    showSettings?: boolean;
}

withDefaults(defineProps<Props>(), {
    showSettings: true,
});

const router = useRouter();
const auth = useAuthStore();

const handleLogout = async () => {
    await auth.logout();
    router.push(AUTH_ROUTES.login.path);
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup v-if="showSettings">
        <DropdownMenuItem as-child>
            <RouterLink class="block w-full" :to="APP_ROUTES.settings.profile.path" as="button">
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </RouterLink>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem as-child>
        <button class="block w-full text-left" @click="handleLogout">
            <LogOut class="mr-2 inline-block h-4 w-4" />
            Log out
        </button>
    </DropdownMenuItem>
</template>
