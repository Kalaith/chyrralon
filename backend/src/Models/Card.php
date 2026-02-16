<?php

declare(strict_types=1);

namespace Chyrralon\Models;

abstract class Card
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $type,
        public readonly int $cost,
        public readonly string $description,
        public readonly ?string $artUrl = null
    ) {}

    abstract public function toArray(): array;

    public function getBaseArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'cost' => $this->cost,
            'description' => $this->description,
            'artUrl' => $this->artUrl
        ];
    }
}

class BaseStats
{
    public function __construct(
        public int $attack,
        public int $health,
        public int $armor = 0
    ) {}

    public function toArray(): array
    {
        return [
            'attack' => $this->attack,
            'health' => $this->health,
            'armor' => $this->armor
        ];
    }
}

class ProceduralModifier
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly array $effect
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'effect' => $this->effect
        ];
    }
}