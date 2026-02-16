import { createSlice, PayloadAction } from '@reduxjs/toolkit';
import { GameState, GamePhase } from '../types/game';
import {
  GameCard,
  CreatureInstance,
  CardType,
  BaseCreatureCard,
  MutationCard,
  BaseStats,
  EnvironmentCard,
} from '../types/cards';

const initialState: GameState = {
  id: '',
  players: [
    {
      id: 'player1',
      name: 'Player 1',
      health: 20,
      dnaPoints: 3,
      energy: 3,
      hand: [],
      deck: [],
      creatures: [],
    },
    {
      id: 'player2',
      name: 'Player 2',
      health: 20,
      dnaPoints: 3,
      energy: 3,
      hand: [],
      deck: [],
      creatures: [],
    },
  ],
  currentPlayerIndex: 0,
  turn: 1,
  phase: GamePhase.Setup,
  battlefield: {
    effects: [],
  },
};

const gameSlice = createSlice({
  name: 'game',
  initialState,
  reducers: {
    initializeGame: (state, action: PayloadAction<{ gameId: string; cards: GameCard[] }>) => {
      state.id = action.payload.gameId;
      // Initialize player decks with sample cards
      state.players.forEach(player => {
        player.deck = [...action.payload.cards];
        player.hand = player.deck.splice(0, 5); // Draw initial hand
      });
      state.phase = GamePhase.Main;
    },

    summonCreature: (
      state,
      action: PayloadAction<{
        playerId: string;
        cardId: string;
        position: { x: number; y: number };
      }>
    ) => {
      const player = state.players.find(p => p.id === action.payload.playerId);
      if (!player) return;

      const cardIndex = player.hand.findIndex(card => card.id === action.payload.cardId);
      if (cardIndex === -1) return;

      const card = player.hand[cardIndex];
      if (card.type === CardType.BaseCreature && player.energy >= card.cost) {
        const baseCard = card as BaseCreatureCard;
        const creature: CreatureInstance = {
          id: `creature_${Date.now()}`,
          baseCard,
          currentStats: { ...baseCard.stats },
          appliedMutations: [],
          evolutionHistory: [],
          activeAbilities: [],
          proceduralTraits: [],
          isEvolved: false,
          position: action.payload.position,
        };

        player.creatures.push(creature);
        player.hand.splice(cardIndex, 1);
        player.energy -= card.cost;
      }
    },

    applyMutation: (
      state,
      action: PayloadAction<{
        playerId: string;
        creatureId: string;
        mutationCardId: string;
      }>
    ) => {
      const player = state.players.find(p => p.id === action.payload.playerId);
      if (!player) return;

      const creature = player.creatures.find(c => c.id === action.payload.creatureId);
      const mutationCard = player.hand.find(card => card.id === action.payload.mutationCardId);

      if (
        creature &&
        mutationCard &&
        mutationCard.type === CardType.Mutation &&
        player.dnaPoints >= mutationCard.cost
      ) {
        const typedMutation = mutationCard as MutationCard;
        creature.appliedMutations.push(typedMutation);

        // Apply stat changes
        const primaryEffect = typedMutation.primaryEffect;
        if (primaryEffect.statChanges) {
          (Object.keys(primaryEffect.statChanges) as Array<keyof BaseStats>).forEach(stat => {
            const delta = primaryEffect.statChanges[stat];
            if (typeof delta === 'number') {
              creature.currentStats[stat] += delta;
            }
          });
        }

        // Add abilities
        if (primaryEffect.abilities) {
          creature.activeAbilities.push(...primaryEffect.abilities);
        }

        player.dnaPoints -= mutationCard.cost;
        player.hand = player.hand.filter(card => card.id !== action.payload.mutationCardId);
      }
    },

    nextPhase: state => {
      // This will be handled by the server-side phase processing
      // For now, just update the phase locally - the server will override
      const phases = Object.values(GamePhase);
      const currentIndex = phases.indexOf(state.phase);
      if (currentIndex < phases.length - 1) {
        state.phase = phases[currentIndex + 1];
      } else {
        state.phase = GamePhase.Main;
        state.currentPlayerIndex = state.currentPlayerIndex === 0 ? 1 : 0;
        state.turn += 1;
      }
    },

    updateGameState: (state, action: PayloadAction<GameState>) => {
      // Replace entire game state from server
      Object.assign(state, action.payload);
    },

    setEnvironment: (state, action: PayloadAction<EnvironmentCard>) => {
      if (action.payload.type === CardType.Environment) {
        state.battlefield.environment = action.payload;
      }
    },
  },
});

export const {
  initializeGame,
  summonCreature,
  applyMutation,
  nextPhase,
  updateGameState,
  setEnvironment,
} = gameSlice.actions;

export default gameSlice.reducer;
