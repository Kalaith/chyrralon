<?php

declare(strict_types=1);

namespace Chyrralon\Models;

class MutationCard extends Card
{
    public function __construct(
        string $id,
        string $name,
        int $cost,
        string $description,
        public readonly string $mutationType,
        public readonly string $targetSlot,
        public readonly array $primaryEffect,
        public readonly array $proceduralModifiers = [],
        ?string $artUrl = null
    ) {
        parent::__construct($id, $name, 'mutation', $cost, $description, $artUrl);
    }

    public function toArray(): array
    {
        return array_merge($this->getBaseArray(), [
            'mutationType' => $this->mutationType,
            'targetSlot' => $this->targetSlot,
            'primaryEffect' => $this->primaryEffect,
            'proceduralModifiers' => array_map(
                fn($modifier) => $modifier instanceof ProceduralModifier ? $modifier->toArray() : $modifier,
                $this->proceduralModifiers
            )
        ]);
    }
}