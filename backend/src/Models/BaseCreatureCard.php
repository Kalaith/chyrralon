<?php

declare(strict_types=1);

namespace Chyrralon\Models;

class BaseCreatureCard extends Card
{
    public function __construct(
        string $id,
        string $name,
        int $cost,
        string $description,
        public readonly BaseStats $stats,
        public readonly array $dnaSlots,
        public readonly int $maxMutations,
        ?string $artUrl = null
    ) {
        parent::__construct($id, $name, 'base_creature', $cost, $description, $artUrl);
    }

    public function toArray(): array
    {
        return array_merge($this->getBaseArray(), [
            'stats' => $this->stats->toArray(),
            'dnaSlots' => $this->dnaSlots,
            'maxMutations' => $this->maxMutations
        ]);
    }
}