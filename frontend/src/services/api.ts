import { GameCard } from '../types/cards';
import { GameState } from '../types/game';
import { AuthUser, getActiveAuthToken } from '../stores/authStore';
import { apiClient } from '../api/apiClient';
import { ApiError } from '../api/types';
import axios, { AxiosRequestConfig } from 'axios';

interface ApiErrorBody {
  error?: string;
  message?: string;
}

const getErrorMessage = (payload: unknown, fallback: string): string => {
  if (typeof payload === 'object' && payload !== null && 'error' in payload) {
    const { error } = payload as ApiErrorBody;
    if (typeof error === 'string' && error.length > 0) {
      return error;
    }
  }
  if (typeof payload === 'object' && payload !== null && 'message' in payload) {
    const { message } = payload as ApiErrorBody;
    if (typeof message === 'string' && message.length > 0) {
      return message;
    }
  }
  return fallback;
};

interface SessionData {
  user?: AuthUser;
  token?: string;
  linked?: boolean;
  moved_games?: number;
}

interface LoginInfoResponse {
  login_url: string;
}

interface CardsResponse {
  cards?: GameCard[];
}

type ApiPayload<T> = T | { success?: boolean; data?: T; error?: string; message?: string };

const unwrap = <T>(payload: ApiPayload<T>, fallback: string): T => {
  if (typeof payload === 'object' && payload !== null && 'success' in payload) {
    const response = payload as { success?: boolean; data?: T; error?: string; message?: string };
    if (response.success === false) {
      throw new ApiError(response.error || response.message || fallback);
    }
    if (response.data !== undefined) {
      return response.data;
    }
  }

  return payload as T;
};

const request = async <T>(config: AxiosRequestConfig, fallback: string): Promise<T> => {
  try {
    const response = await apiClient.request<ApiPayload<T>>(config);
    return unwrap<T>(response.data, fallback);
  } catch (error: unknown) {
    if (axios.isAxiosError(error)) {
      const status = error.response?.status ?? 500;
      throw new ApiError(getErrorMessage(error.response?.data, fallback), status);
    }

    throw error;
  }
};

const authHeaders = (token?: string): Record<string, string> => {
  const activeToken = token ?? getActiveAuthToken();
  return activeToken ? { Authorization: `Bearer ${activeToken}` } : {};
};

export class GameAPI {
  static async getLoginInfo(): Promise<LoginInfoResponse> {
    return request<LoginInfoResponse>(
      {
        method: 'GET',
        url: '/auth/login-info',
      },
      'Failed to load login info'
    );
  }

  static async getSession(token?: string): Promise<AuthUser> {
    const data = await request<SessionData>(
      {
        method: 'GET',
        url: '/auth/session',
        headers: authHeaders(token),
      },
      'Failed to load session'
    );

    if (!data.user) {
      throw new ApiError(getErrorMessage(data, 'Failed to load session'));
    }

    return data.user;
  }

  static async createGuestSession(): Promise<{ user: AuthUser; token: string }> {
    const data = await request<SessionData>(
      {
        method: 'POST',
        url: '/auth/guest-session',
      },
      'Failed to create guest session'
    );

    if (!data.user || !data.token) {
      throw new ApiError(getErrorMessage(data, 'Failed to create guest session'));
    }

    return {
      user: data.user,
      token: data.token,
    };
  }

  static async linkGuestAccount(guestToken: string, token: string): Promise<AuthUser> {
    const data = await request<SessionData>(
      {
        method: 'POST',
        url: '/auth/link-guest',
        headers: authHeaders(token),
        data: {
          guest_token: guestToken,
        },
      },
      'Failed to link guest account'
    );

    if (!data.user) {
      throw new ApiError(getErrorMessage(data, 'Failed to link guest account'));
    }

    return data.user;
  }

  static async createGame(): Promise<GameState> {
    return request<GameState>(
      {
        method: 'POST',
        url: '/game/create',
      },
      'Failed to create game'
    );
  }

  static async getGame(gameId: string): Promise<GameState> {
    return request<GameState>(
      {
        method: 'GET',
        url: `/game/${gameId}`,
      },
      'Failed to get game'
    );
  }

  static async processPhase(gameId: string): Promise<GameState> {
    return request<GameState>(
      {
        method: 'POST',
        url: `/game/${gameId}/phase`,
      },
      'Failed to process phase'
    );
  }

  static async summonCreature(
    gameId: string,
    data: {
      playerId: string;
      cardId: string;
      position: { x: number; y: number };
    }
  ): Promise<GameState> {
    return request<GameState>(
      {
        method: 'POST',
        url: `/game/${gameId}/summon`,
        data,
      },
      'Failed to summon creature'
    );
  }

  static async applyMutation(
    gameId: string,
    data: {
      playerId: string;
      creatureId: string;
      mutationCardId: string;
    }
  ): Promise<GameState> {
    return request<GameState>(
      {
        method: 'POST',
        url: `/game/${gameId}/mutate`,
        data,
      },
      'Failed to apply mutation'
    );
  }

  static async getCards(): Promise<GameCard[]> {
    const data = await request<GameCard[] | CardsResponse>(
      {
        method: 'GET',
        url: '/cards',
      },
      'Failed to load cards'
    );
    return Array.isArray(data) ? data : (data.cards ?? []);
  }
}
