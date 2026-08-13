import type { ApiResponse } from '@/types';
import axios, { AxiosError } from 'axios';

export const TOKEN_STORAGE_KEY = 'konexus_token';
export const ORIGINAL_TOKEN_STORAGE_KEY = 'konexus_original_token';

export function getStoredToken(): string | null {
    return localStorage.getItem(TOKEN_STORAGE_KEY);
}

export function storeToken(token: string | null): void {
    if (token) {
        localStorage.setItem(TOKEN_STORAGE_KEY, token);
    } else {
        localStorage.removeItem(TOKEN_STORAGE_KEY);
    }
}

export function getOriginalToken(): string | null {
    return localStorage.getItem(ORIGINAL_TOKEN_STORAGE_KEY);
}

export function storeOriginalToken(token: string | null): void {
    if (token) {
        localStorage.setItem(ORIGINAL_TOKEN_STORAGE_KEY, token);
    } else {
        localStorage.removeItem(ORIGINAL_TOKEN_STORAGE_KEY);
    }
}

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api/v1',
    headers: {
        Accept: 'application/json',
    },
});

// Attach the bearer token to every request...
api.interceptors.request.use((config) => {
    const token = getStoredToken();

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

let onUnauthorized: (() => void) | null = null;

export function setUnauthorizedHandler(handler: (() => void) | null): void {
    onUnauthorized = handler;
}

// Normalize backend validation errors and propagate 401s to the auth layer...
api.interceptors.response.use(
    (response) => response,
    (error: AxiosError<ApiResponse>) => {
        if (error.response?.status === 401 && onUnauthorized) {
            onUnauthorized();
        }

        return Promise.reject(error);
    },
);

export function extractError(error: unknown): string {
    if (axios.isAxiosError<ApiResponse>(error)) {
        return error.response?.data?.message || error.message || 'Something went wrong.';
    }

    if (error instanceof Error) {
        return error.message;
    }

    return 'Something went wrong.';
}

export function extractFieldErrors(error: unknown): Record<string, string[]> {
    if (axios.isAxiosError<ApiResponse>(error)) {
        return error.response?.data?.errors ?? {};
    }

    return {};
}

export default api;
