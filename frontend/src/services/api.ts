import { GameCard } from '../types/cards';
import { GameState } from '../types/game';

const apiBase = 'http://localhost:8000/api';

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

const getAuthToken = (): string | null => {
  try {
    const raw = localStorage.getItem('auth-storage');
    if (!raw) return null;
    const parsed = JSON.parse(raw) as { state?: { token?: string | null } };
    return parsed.state?.token ?? null;
  } catch {
    return null;
  }
};

const withAuth = (headers: Record<string, string> = {}) => {
  const token = getAuthToken();
  return token ? { ...headers, Authorization: `Bearer ${token}` } : headers;
};

export class GameAPI {
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
