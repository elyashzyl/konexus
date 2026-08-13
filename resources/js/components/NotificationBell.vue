<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNotificationsStore } from '@/stores/notifications';
import { Bell } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

const store = useNotificationsStore();

const unreadCount = computed(() => store.unread);

onMounted(async () => {
    await store.load();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative" aria-label="Notifications">
                <Bell class="size-4" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute right-1.5 top-1.5 flex size-2 rounded-full bg-destructive"
                    aria-hidden="true"
                />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between">
                <span>Notifications</span>
                <Button v-if="unreadCount > 0" variant="ghost" size="sm" class="h-7 text-xs" @click="store.markAllRead()">
                    Mark all read
                </Button>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />

            <div class="max-h-80 overflow-y-auto">
                <DropdownMenuItem v-for="item in store.items.slice(0, 8)" :key="item.id" class="cursor-pointer items-start gap-2" @click="store.markRead(item.id)">
                    <span class="mt-1 size-1.5 shrink-0 rounded-full" :class="item.read_at ? 'bg-transparent' : 'bg-primary'" />
                    <span class="flex flex-col gap-0.5 py-1">
                        <span class="text-sm font-medium leading-none">{{ item.title }}</span>
                        <span class="line-clamp-2 text-xs text-muted-foreground">{{ item.body }}</span>
                        <span class="text-[10px] text-muted-foreground/70">{{ new Date(item.created_at).toLocaleString() }}</span>
                    </span>
                </DropdownMenuItem>

                <div v-if="store.items.length === 0" class="px-3 py-8 text-center text-sm text-muted-foreground">
                    You're all caught up.
                </div>
            </div>

            <DropdownMenuSeparator />
            <DropdownMenuItem as-child class="justify-center text-center">
                <RouterLink to="/notifications" class="w-full text-center text-sm">View all notifications</RouterLink>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
