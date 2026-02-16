<?php

declare(strict_types=1);

namespace Chyrralon\Data;

use Chyrralon\Models\BaseCreatureCard;
use Chyrralon\Models\MutationCard;
use Chyrralon\Models\EvolutionCard;
use Chyrralon\Models\BaseStats;
use Chyrralon\Models\ProceduralModifier;

class SampleCards
{
    public static function getBaseCreatures(): array
    {
        return [
            new BaseCreatureCard(
                id: 'grub_001',
                name: 'Grub',
                cost: 1,
                description: 'A simple larval creature with great potential for growth',
                stats: new BaseStats(attack: 1, health: 3),
                dnaSlots: ['body', 'attack', 'defense'],
                maxMutations: 3
            ),
            new BaseCreatureCard(
                id: 'spore_001',
                name: 'Spore',
                cost: 1,
                description: 'A fungal organism that can adapt to various environments',
                stats: new BaseStats(attack: 0, health: 2),
                dnaSlots: ['essence', 'defense', 'mind'],
                maxMutations: 2
            )
        ];
    }

    public static function getMutations(): array
    {
        return [
            new MutationCard(
                id: 'spikes_001',
                name: 'Spikes',
                cost: 2,
                description: 'Sharp protrusions that increase attack and provide thorns',
                mutationType: 'spikes',
                targetSlot: 'attack',
                primaryEffect: [
                    'statChanges' => ['attack' => 2],
                    'abilities' => ['thorns']
                ],
                proceduralModifiers: [
                    new ProceduralModifier(
                        id: 'poison_spikes',
                        name: 'Poison Spikes',
                        description: 'Chance for spikes to be poisonous',
                        effect: ['poison_chance' => 0.3]
                    )
                ]
            ),
            new MutationCard(
                id: 'carapace_001',
                name: 'Carapace',
                cost: 3,
                description: 'Hardened shell that provides armor and durability',
                mutationType: 'carapace',
                targetSlot: 'defense',
                primaryEffect: [
                    'statChanges' => ['armor' => 3, 'health' => 1],
                    'abilities' => ['armored']
                ],
                proceduralModifiers: [
                    new ProceduralModifier(
                        id: 'reinforced_armor',
                        name: 'Reinforced Armor',
                        description: 'Extra durability against physical attacks',
                        effect: ['physical_resistance' => 0.2]
                    )
                ]
            )
        ];
    }

    public static function getEvolutions(): array
    {
        return [
            new EvolutionCard(
                id: 'beetle_warrior_001',
                name: 'Beetle Warrior',
                cost: 0,
                description: 'A grub evolved with spikes and carapace into a formidable warrior',
                requiredMutations: ['spikes', 'carapace'],
                baseCreatureType: 'grub_001',
                evolvedStats: new BaseStats(attack: 5, health: 6, armor: 2),
                evolvedAbilities: ['thorns', 'armored', 'charge'],
                proceduralTraits: [
                    new ProceduralModifier(
                        id: 'battle_fury',
                        name: 'Battle Fury',
                        description: 'Gains attack when damaged',
                        effect: ['fury_trigger' => 'on_damage', 'attack_bonus' => 1]
                    )
                ]
            )
        ];
    }

    public static function getAllCards(): array
    {
        return array_merge(
            self::getBaseCreatures(),
            self::getMutations(),
            self::getEvolutions()
        );
    }
}