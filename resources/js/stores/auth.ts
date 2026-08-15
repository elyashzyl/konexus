import api, { extractError, getOriginalToken, getStoredToken, setUnauthorizedHandler, storeOriginalToken, storeToken } from '@/lib/api';
import type { AuthPayload, Role, Session, User } from '@/types';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

interface LoginPayload {
    email: string;
    password: string;
    remember?: boolean;
}

interface RegisterPayload {
    school_name: string;
    short_name?: string;
    school_id?: string;
    region?: string;
    division?: string;
    district?: string;
    address?: string;
    contact_number?: string;
    school_email?: string;
    website?: string;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const token = ref<string | null>(getStoredToken());
    const originalToken = ref<string | null>(getOriginalToken());
    const impersonator = ref<User | null>(null);
    const status = ref<'idle' | 'loading' | 'authenticated' | 'unauthenticated'>('idle');
    const initialized = ref(false);

    const isAuthenticated = computed(() => status.value === 'authenticated');
    const isActive = computed(() => user.value?.is_active ?? false);
    const isImpersonating = computed(() => originalToken.value !== null);
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

    async function socialLogin(socialToken: string): Promise<User> {
        storeToken(socialToken);
        token.value = socialToken;
        status.value = 'authenticated';

        try {
            await fetchMe();
        } catch {
            clearAuth();
            throw new Error('We could not load your profile. Please try again.');
        }

        return user.value as User;
    }

    function clearAuth(): void {
        token.value = null;
        user.value = null;
        originalToken.value = null;
        impersonator.value = null;
        status.value = 'unauthenticated';
        storeToken(null);
        storeOriginalToken(null);
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

    async function impersonate(userId: number): Promise<User> {
        const response = await api.post<{
            data: { token: string; user: User; impersonator: User };
        }>(`/users/${userId}/impersonate`);
        const data = response.data.data;

        originalToken.value = token.value;
        impersonator.value = data.impersonator;
        storeOriginalToken(originalToken.value);

        token.value = data.token;
        user.value = data.user;
        storeToken(token.value);
        status.value = 'authenticated';

        return user.value;
    }

    async function stopImpersonating(): Promise<void> {
        const original = originalToken.value;

        try {
            await api.post('/users/stop-impersonating');
        } finally {
            if (original) {
                token.value = original;
                storeToken(original);
                originalToken.value = null;
                impersonator.value = null;
                storeOriginalToken(null);

                await fetchMe();
                status.value = 'authenticated';
            } else {
                clearAuth();
            }
        }
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
        originalToken,
        impersonator,
        status,
        initialized,
        isAuthenticated,
        isActive,
        isImpersonating,
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
        socialLogin,
        clearAuth,
        impersonate,
        stopImpersonating,
        sessions,
        revokeSession,
        revokeAllSessions,
        forgotPassword,
        resetPassword,
        fetchRoleCatalog,
    };
});

export const useAuthError = extractError;
