import {
  GameCard,
  CardType,
  DNASlot,
  MutationType,
  BaseCreatureCard,
  MutationCard,
  EvolutionCard,
} from '../types/cards';

const grubCard: BaseCreatureCard = {
  id: 'grub_001',
  name: 'Grub',
  type: CardType.BaseCreature,
  cost: 1,
  description: 'A simple larval creature with great potential for growth',
  stats: { attack: 1, health: 3, armor: 0 },
  dnaSlots: [DNASlot.Body, DNASlot.Attack, DNASlot.Defense],
  maxMutations: 3,
};

const sporeCard: BaseCreatureCard = {
  id: 'spore_001',
  name: 'Spore',
  type: CardType.BaseCreature,
  cost: 1,
  description: 'A fungal organism that can adapt to various environments',
  stats: { attack: 0, health: 2, armor: 0 },
  dnaSlots: [DNASlot.Essence, DNASlot.Defense, DNASlot.Mind],
  maxMutations: 2,
};

const spikesMutation: MutationCard = {
  id: 'spikes_001',
  name: 'Spikes',
  type: CardType.Mutation,
  cost: 2,
  description: 'Sharp protrusions that increase attack and provide thorns',
  mutationType: MutationType.Spikes,
  targetSlot: DNASlot.Attack,
  primaryEffect: {
    statChanges: { attack: 2 },
    abilities: ['thorns'],
  },
  proceduralModifiers: [],
};

const carapaceMutation: MutationCard = {
  id: 'carapace_001',
  name: 'Carapace',
  type: CardType.Mutation,
  cost: 3,
  description: 'Hardened shell that provides armor and durability',
  mutationType: MutationType.Carapace,
  targetSlot: DNASlot.Defense,
  primaryEffect: {
    statChanges: { armor: 3, health: 1 },
    abilities: ['armored'],
  },
  proceduralModifiers: [],
};

const beetleWarriorEvolution: EvolutionCard = {
  id: 'beetle_warrior_001',
  name: 'Beetle Warrior',
  type: CardType.Evolution,
  cost: 0,
  description: 'A grub evolved with spikes and carapace into a formidable warrior',
  requiredMutations: [MutationType.Spikes, MutationType.Carapace],
  baseCreatureType: 'grub_001',
  evolvedStats: { attack: 5, health: 6, armor: 2 },
  evolvedAbilities: ['thorns', 'armored', 'charge'],
  proceduralTraits: [],
};

export const sampleCards: GameCard[] = [
  grubCard,
  sporeCard,
  spikesMutation,
  carapaceMutation,
  beetleWarriorEvolution,
];
