import api from '@/lib/api';

export type SocialProvider = 'google' | 'facebook';

export interface SocialRedirectPayload {
    url: string;
    intended?: string;
}

const SOCIAL_PROVIDERS: SocialProvider[] = ['google', 'facebook'];

export function isSocialProvider(value: unknown): value is SocialProvider {
    return typeof value === 'string' && (SOCIAL_PROVIDERS as string[]).includes(value);
}

/**
 * Ask the API for the provider authorization URL, then hand off to it.
 * An optional `intended` path is carried through the OAuth round-trip so the
 * browser can be sent somewhere specific (e.g. `/enrollment`) afterwards.
 */
export async function redirectToProvider(provider: SocialProvider, intended?: string): Promise<void> {
    const { data } = await api.get<{ data: SocialRedirectPayload }>(`/auth/${provider}/redirect`, {
        params: intended ? { intended } : undefined,
    });

    window.location.href = data.data.url;
}