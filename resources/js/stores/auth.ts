import api, { extractError, getStoredToken, setUnauthorizedHandler, storeToken } from '@/lib/api';
import type { AuthPayload, Role, Session, User } from '@/types';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

interface LoginPayload {
    email: string;
    password: string;
    remember?: boolean;
}

interface RegisterPayload {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const token = ref<string | null>(getStoredToken());
    const status = ref<'idle' | 'loading' | 'authenticated' | 'unauthenticated'>('idle');
    const initialized = ref(false);

    const isAuthenticated = computed(() => status.value === 'authenticated');
    const isActive = computed(() => user.value?.is_active ?? false);
    const primaryRole = computed<Role | null>(() => user.value?.roles?.[0] ?? null);
    const can = computed(() => (role: string) => user.value?.roles?.some((r) => r.name === role) ?? false);

    async function fetchMe(): Promise<User> {
        const response = await api.get<{ data: User }>('/auth/me');
        user.value = response.data.data;

        return user.value;
    }

    async function updateProfile(payload: { name: string; email: string }): Promise<User> {
        const response = await api.patch<{ data: User }>('/auth/me', payload);
        user.value = response.data.data;

        return user.value;
    }

    async function changePassword(payload: { current_password: string; password: string; password_confirmation: string }): Promise<void> {
        await api.put('/auth/password', payload);
    }

    async function deleteAccount(payload: { password: string }): Promise<void> {
        await api.delete('/auth/me', { data: payload });
        clearAuth();
    }

    async function login(payload: LoginPayload): Promise<AuthPayload> {
        status.value = 'loading';

        try {
            const response = await api.post<{ data: AuthPayload }>('/auth/login', payload);
            token.value = response.data.data.token;
            user.value = response.data.data.user;
            storeToken(token.value);
            status.value = 'authenticated';

            return response.data.data;
        } catch (error) {
            status.value = 'unauthenticated';
            throw error;
        }
    }

    async function register(payload: RegisterPayload): Promise<AuthPayload> {
        status.value = 'loading';

        try {
            const response = await api.post<{ data: AuthPayload }>('/auth/register', payload);
            token.value = response.data.data.token;
            user.value = response.data.data.user;
            storeToken(token.value);
            status.value = 'authenticated';

            return response.data.data;
        } catch (error) {
            status.value = 'unauthenticated';
            throw error;
        }
    }

    async function logout(): Promise<void> {
        try {
            await api.post('/auth/logout');
        } finally {
            clearAuth();
        }
    }

    function clearAuth(): void {
        token.value = null;
        user.value = null;
        status.value = 'unauthenticated';
        storeToken(null);
    }

    async function initialize(): Promise<void> {
        if (initialized.value) {
            return;
        }

        if (getStoredToken()) {
            status.value = 'loading';

            try {
                await fetchMe();
                status.value = 'authenticated';
            } catch {
                clearAuth();
            }
        } else {
            status.value = 'unauthenticated';
        }

        initialized.value = true;
    }

    async function sessions(): Promise<Session[]> {
        const response = await api.get<{ data: Session[] }>('/auth/sessions');

        return response.data.data;
    }

    async function revokeSession(id: number): Promise<void> {
        await api.delete(`/auth/sessions/${id}`);
    }

    async function revokeAllSessions(): Promise<void> {
        await api.delete('/auth/sessions');
    }

    async function forgotPassword(email: string): Promise<void> {
        await api.post('/auth/forgot-password', { email });
    }

    async function resetPassword(payload: { email: string; password: string; password_confirmation: string; token: string }): Promise<void> {
        await api.post('/auth/reset-password', payload);
    }

    async function fetchRoleCatalog(): Promise<{ key: string; label: string; description: string }[]> {
        const response = await api.get<{ data: { key: string; label: string; description: string }[] }>('/roles/catalog');

        return response.data.data;
    }

    setUnauthorizedHandler(clearAuth);

    return {
        user,
        token,
        status,
        initialized,
        isAuthenticated,
        isActive,
        primaryRole,
        can,
        initialize,
        fetchMe,
        updateProfile,
        changePassword,
        deleteAccount,
        login,
        register,
        logout,
        clearAuth,
        sessions,
        revokeSession,
        revokeAllSessions,
        forgotPassword,
        resetPassword,
        fetchRoleCatalog,
    };
});

export const useAuthError = extractError;
