import { EnvironmentCard, GameCard, CreatureInstance } from './cards';

export interface Player {
  id: string;
  name: string;
  health: number;
  dnaPoints: number;
  energy: number;
  hand: GameCard[];
  deck: GameCard[];
  creatures: CreatureInstance[];
}

export interface GameState {
  id: string;
  players: [Player, Player];
  currentPlayerIndex: number;
  turn: number;
  phase: GamePhase;
  battlefield: {
    environment?: EnvironmentCard;
    effects: unknown[];
  };
  winner?: string;
}

export enum GamePhase {
  Setup = 'setup',
  Main = 'main',
  Mutation = 'mutation',
  Combat = 'combat',
  End = 'end',
}

export interface GameAction {
  type: string;
  playerId: string;
  payload: unknown;
}
