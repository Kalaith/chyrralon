import { GameCard } from '../types/cards';
import { GameState } from '../types/game';
import { AuthUser, getActiveAuthToken } from '../stores/authStore';

const configuredApiBase = import.meta.env.VITE_API_URL as string | undefined;

if (!configuredApiBase || configuredApiBase.trim().length === 0) {
  throw new Error('VITE_API_URL is required');
}

const apiBase = configuredApiBase.replace(/\/$/, '');

interface ApiErrorBody {
  error?: string;
}

const getErrorMessage = (payload: unknown, fallback: string): string => {
  if (typeof payload === 'object' && payload !== null && 'error' in payload) {
    const { error } = payload as ApiErrorBody;
    if (typeof error === 'string' && error.length > 0) {
      return error;
    }
  }
  return fallback;
};

const withAuth = (headers: Record<string, string> = {}) => {
  const token = getActiveAuthToken();
  return token ? { ...headers, Authorization: `Bearer ${token}` } : headers;
};

interface SessionResponse {
  success: boolean;
  data?: {
    user?: AuthUser;
    token?: string;
    linked?: boolean;
    guest_user_id?: string;
  };
  error?: string;
  login_url?: string;
}

export class GameAPI {
  static async getSession(token?: string): Promise<AuthUser> {
    const response = await fetch(`${apiBase}/auth/session`, {
      headers: token ? { Authorization: `Bearer ${token}` } : withAuth(),
    });
    const data = (await response.json()) as SessionResponse;

    if (!response.ok || !data.data?.user) {
      throw new Error(getErrorMessage(data, 'Failed to load session'));
    }

    return data.data.user;
  }

  static async createGuestSession(): Promise<{ user: AuthUser; token: string }> {
    const response = await fetch(`${apiBase}/auth/guest-session`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    });
    const data = (await response.json()) as SessionResponse;

    if (!response.ok || !data.data?.user || !data.data?.token) {
      throw new Error(getErrorMessage(data, 'Failed to create guest session'));
    }

    return {
      user: data.data.user,
      token: data.data.token,
    };
  }

  static async linkGuestAccount(guestUserId: string, token: string): Promise<AuthUser> {
    const response = await fetch(`${apiBase}/auth/link-guest`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        guest_user_id: guestUserId,
      }),
    });
    const data = (await response.json()) as SessionResponse;

    if (!response.ok || !data.data?.user) {
      throw new Error(getErrorMessage(data, 'Failed to link guest account'));
    }

    return data.data.user;
  }

  static async createGame(): Promise<GameState> {
    const response = await fetch(`${apiBase}/game/create`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...withAuth(),
      },
    });
    const data = (await response.json()) as unknown;
    if (!response.ok) {
      throw new Error(getErrorMessage(data, 'Failed to create game'));
    }
    return data as GameState;
  }

  static async getGame(gameId: string): Promise<GameState> {
    const response = await fetch(`${apiBase}/game/${gameId}`, {
      headers: withAuth(),
    });
    const data = (await response.json()) as unknown;
    if (!response.ok) {
      throw new Error(getErrorMessage(data, 'Failed to get game'));
    }
    return data as GameState;
  }

  static async processPhase(gameId: string): Promise<GameState> {
    const response = await fetch(`${apiBase}/game/${gameId}/phase`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...withAuth(),
      },
    });
    const data = (await response.json()) as unknown;
    if (!response.ok) {
      throw new Error(getErrorMessage(data, 'Failed to process phase'));
    }
    return data as GameState;
  }

  static async summonCreature(
    gameId: string,
    data: {
      playerId: string;
      cardId: string;
      position: { x: number; y: number };
    }
  ): Promise<GameState> {
    const response = await fetch(`${apiBase}/game/${gameId}/summon`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...withAuth(),
      },
      body: JSON.stringify(data),
    });
    const result = (await response.json()) as unknown;
    if (!response.ok) {
      throw new Error(getErrorMessage(result, 'Failed to summon creature'));
    }
    return result as GameState;
  }

  static async applyMutation(
    gameId: string,
    data: {
      playerId: string;
      creatureId: string;
      mutationCardId: string;
    }
  ): Promise<GameState> {
    const response = await fetch(`${apiBase}/game/${gameId}/mutate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...withAuth(),
      },
      body: JSON.stringify(data),
    });
    const result = (await response.json()) as unknown;
    if (!response.ok) {
      throw new Error(getErrorMessage(result, 'Failed to apply mutation'));
    }
    return result as GameState;
  }

  static async getCards(): Promise<GameCard[]> {
    const response = await fetch(`${apiBase}/cards`, {
      headers: withAuth(),
    });
    return (await response.json()) as GameCard[];
  }
}
