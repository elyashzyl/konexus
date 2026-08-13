import { platformApi } from '@/lib/platformApi';
import type { AppNotification, NotificationPreferenceMatrix } from '@/types/platform';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useNotificationsStore = defineStore('notifications', () => {
    const unread = ref(0);
    const items = ref<AppNotification[]>([]);
    const preferences = ref<NotificationPreferenceMatrix>({});
    const initialized = ref(false);

    async function refreshUnread(): Promise<void> {
        try {
            const { unread: count } = await platformApi.notifications.unreadCount();
            unread.value = count;
        } catch {
            unread.value = 0;
        }
    }

    async function load(page = 1, perPage = 15): Promise<void> {
        const data = await platformApi.notifications.index({ page, per_page: perPage });
        items.value = data.items;
        await refreshUnread();
    }

    async function markRead(id: string): Promise<void> {
        const updated = await platformApi.notifications.markRead(id);
        const index = items.value.findIndex((item) => item.id === id);
        if (index !== -1) {
            items.value[index] = updated;
        }
        await refreshUnread();
    }

    async function markAllRead(): Promise<void> {
        await platformApi.notifications.markAllRead();
        items.value = items.value.map((item) => ({ ...item, read_at: new Date().toISOString() }));
        unread.value = 0;
    }

    async function loadPreferences(): Promise<void> {
        const data = await platformApi.preferences.get();
        preferences.value = data.matrix;
        initialized.value = true;
    }

    async function savePreferences(matrix: NotificationPreferenceMatrix): Promise<void> {
        const data = await platformApi.preferences.update(matrix);
        preferences.value = data.matrix;
    }

    return {
        unread,
        items,
        preferences,
        initialized,
        refreshUnread,
        load,
        markRead,
        markAllRead,
        loadPreferences,
        savePreferences,
    };
});
