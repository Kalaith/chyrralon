export enum CardType {
  BaseCreature = 'base_creature',
  Mutation = 'mutation',
  Evolution = 'evolution',
  Spell = 'spell',
  Environment = 'environment',
}

export enum DNASlot {
  Body = 'body',
  Attack = 'attack',
  Defense = 'defense',
  Mind = 'mind',
  Essence = 'essence',
}

export enum MutationType {
  Spikes = 'spikes',
  Carapace = 'carapace',
  Wings = 'wings',
  Venom = 'venom',
  Regeneration = 'regeneration',
}

export interface BaseStats {
  attack: number;
  health: number;
  armor: number;
}

export interface ProceduralModifier {
  id: string;
  name: string;
  description: string;
  effect: Record<string, unknown>;
}

export interface BaseCard {
  id: string;
  name: string;
  type: CardType;
  cost: number;
  description: string;
  artUrl?: string;
}

export interface BaseCreatureCard extends BaseCard {
  type: CardType.BaseCreature;
  stats: BaseStats;
  dnaSlots: DNASlot[];
  maxMutations: number;
}

export interface MutationCard extends BaseCard {
  type: CardType.Mutation;
  mutationType: MutationType;
  targetSlot: DNASlot;
  primaryEffect: {
    statChanges: Partial<BaseStats>;
    abilities: string[];
  };
  proceduralModifiers: ProceduralModifier[];
}

export interface EvolutionCard extends BaseCard {
  type: CardType.Evolution;
  requiredMutations: MutationType[];
  baseCreatureType: string;
  evolvedStats: BaseStats;
  evolvedAbilities: string[];
  proceduralTraits: ProceduralModifier[];
}

export interface SpellCard extends BaseCard {
  type: CardType.Spell;
  effect: {
    damage?: number;
    healing?: number;
    targetType: 'creature' | 'player' | 'all' | 'environment';
    description: string;
  };
}

export interface EnvironmentCard extends BaseCard {
  type: CardType.Environment;
  globalEffect: {
    description: string;
    modifiers: Record<string, unknown>;
  };
  adaptationRules: Array<{
    mutationType: MutationType;
    adaptedTrait: ProceduralModifier;
  }>;
}

export type GameCard =
  | BaseCreatureCard
  | MutationCard
  | EvolutionCard
  | SpellCard
  | EnvironmentCard;

export interface CreatureInstance {
  id: string;
  baseCard: BaseCreatureCard;
  currentStats: BaseStats;
  appliedMutations: MutationCard[];
  evolutionHistory: EvolutionCard[];
  activeAbilities: string[];
  proceduralTraits: ProceduralModifier[];
  isEvolved: boolean;
  position?: { x: number; y: number };
}
