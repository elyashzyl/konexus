import api, { getStoredCampusId, storeCampusId } from '@/lib/api';
import { useAuthStore } from '@/stores/auth';
import type { CampusWorkspace, User } from '@/types';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

interface WorkspaceResponse {
    active_campus: CampusWorkspace | null;
    campuses: CampusWorkspace[];
}

export const useWorkspaceStore = defineStore('workspace', () => {
    const campuses = ref<CampusWorkspace[]>([]);
    const activeCampus = ref<CampusWorkspace | null>(null);
    const loading = ref(false);
    const initialized = ref(false);

    const hasMultipleCampuses = computed(() => campuses.value.length > 1);

    async function initialize(force = false): Promise<void> {
        if (initialized.value && !force) {
            return;
        }

        loading.value = true;

        try {
            // Resolve the persisted server-side choice before attaching a
            // campus header. This also recovers gracefully from a deactivated
            // or deleted campus saved by an earlier browser session.
            storeCampusId(null);
            const { data } = await api.get<{ data: WorkspaceResponse }>('/workspaces');
            campuses.value = data.data.campuses;
            activeCampus.value = data.data.active_campus;
            storeCampusId(activeCampus.value?.id ?? null);
            initialized.value = true;
        } finally {
            loading.value = false;
        }
    }

    async function select(campusId: number): Promise<void> {
        if (campusId === activeCampus.value?.id) {
            return;
        }

        loading.value = true;

        try {
            const { data } = await api.put<{ data: { active_campus: CampusWorkspace; user: User } }>('/workspaces/active', { campus_id: campusId });
            activeCampus.value = data.data.active_campus;
            storeCampusId(activeCampus.value.id);
            useAuthStore().user = data.data.user;
        } finally {
            loading.value = false;
        }
    }

    function clear(): void {
        campuses.value = [];
        activeCampus.value = null;
        initialized.value = false;
        storeCampusId(null);
    }

    function selectedCampusId(): number | null {
        return activeCampus.value?.id ?? getStoredCampusId();
    }

    return { campuses, activeCampus, loading, initialized, hasMultipleCampuses, initialize, select, clear, selectedCampusId };
});
