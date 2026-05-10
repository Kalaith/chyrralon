import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export type AuthMode = 'frontpage' | 'guest';

export interface AuthUser {
    id: string;
    email?: string | null;
    username?: string | null;
    display_name?: string | null;
    role?: string;
    roles?: string[];
    auth_type?: AuthMode | string;
    is_guest?: boolean;
}

export interface StoredGuestSession {
    token: string;
    user: AuthUser;
}

export const WEBHATCHERY_AUTH_STORAGE_KEY = 'auth-storage';

export interface AuthState {
    user: AuthUser | null;
    token: string | null;
    authMode: AuthMode | null;
    loginUrl: string | null;
    guestSession: StoredGuestSession | null;
    setLoginUrl: (url: string | null) => void;
    login: (user: AuthUser, token: string, authMode?: AuthMode) => void;
    logout: () => void;
    setGuestSession: (session: StoredGuestSession) => void;
    clearGuestSession: () => void;
}

export const getFrontpageToken = (): string | null => {
    try {
        const raw = localStorage.getItem(WEBHATCHERY_AUTH_STORAGE_KEY);
        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as { state?: { token?: string | null; user?: { is_guest?: boolean } | null } };
        if (parsed.state?.user?.is_guest) {
            return null;
        }

        return parsed.state?.token ?? null;
    } catch {
        return null;
    }
};

export const getStoredGuestSession = (): StoredGuestSession | null => {
    return useAuthStore.getState().guestSession;
};

export const saveGuestSession = (session: StoredGuestSession): void => {
    useAuthStore.getState().setGuestSession(session);
};

export const clearGuestSession = (): void => {
    useAuthStore.getState().clearGuestSession();
};

export const getActiveAuthToken = (): string | null => {
    const frontpageToken = getFrontpageToken();
    if (frontpageToken) {
        return frontpageToken;
    }

    const guestSession = getStoredGuestSession();
    if (guestSession?.token) {
        return guestSession.token;
    }

    return null;
};

export const useAuthStore = create<AuthState>()(
    persist(
        (set) => ({
            user: null,
            token: null,
            authMode: null,
            loginUrl: null,
            guestSession: null,
            setLoginUrl: (url) => set({ loginUrl: url }),
            login: (user, token, authMode = 'frontpage') =>
                set({
                    user,
                    token,
                    authMode,
                    loginUrl: null,
                    guestSession: authMode === 'guest' ? { token, user } : null,
                }),
            logout: () => {
                set({ user: null, token: null, authMode: null, guestSession: null });
            },
            setGuestSession: (session) => set({ guestSession: session }),
            clearGuestSession: () => set({ guestSession: null }),
        }),
        { name: 'chyrralon-auth-store' }
    )
);
