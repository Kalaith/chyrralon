<?php

declare(strict_types=1);

namespace Chyrralon\Models;

class EvolutionCard extends Card
{
    public function __construct(
        string $id,
        string $name,
        int $cost,
        string $description,
        public readonly array $requiredMutations,
        public readonly string $baseCreatureType,
        public readonly BaseStats $evolvedStats,
        public readonly array $evolvedAbilities,
        public readonly array $proceduralTraits = [],
        ?string $artUrl = null
    ) {
        parent::__construct($id, $name, 'evolution', $cost, $description, $artUrl);
    }

    public function toArray(): array
    {
        return array_merge($this->getBaseArray(), [
            'requiredMutations' => $this->requiredMutations,
            'baseCreatureType' => $this->baseCreatureType,
            'evolvedStats' => $this->evolvedStats->toArray(),
            'evolvedAbilities' => $this->evolvedAbilities,
            'proceduralTraits' => array_map(
                fn($trait) => $trait instanceof ProceduralModifier ? $trait->toArray() : $trait,
                $this->proceduralTraits
            )
        ]);
    }
}