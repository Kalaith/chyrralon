<?php

declare(strict_types=1);

namespace Chyrralon\Services;

use Chyrralon\Models\BaseCreatureCard;
use Chyrralon\Models\MutationCard;
use Chyrralon\Models\EvolutionCard;
use Chyrralon\Models\ProceduralModifier;
use Chyrralon\Data\SampleCards;

class GameEngine
{
    private static ?GameEngine $instance = null;
    private array $gameStates = [];

    private function __construct() {
        $this->loadGameStates();
    }

    public static function getInstance(): GameEngine
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    private function processMainPhase(array &$game): void
    {
        // Main phase processing - card plays, summons
        $currentPlayer = &$game['players'][$game['currentPlayerIndex']];
        
        // Draw card if not first turn
        if ($game['turn'] > 1 && !empty($currentPlayer['deck'])) {
            $drawnCard = array_pop($currentPlayer['deck']);
            $currentPlayer['hand'][] = $drawnCard;
        }
    }

    private function processMutationPhase(array &$game): void
    {
        // Check for automatic evolutions and trigger procedural effects
        $currentPlayer = &$game['players'][$game['currentPlayerIndex']];
        
        foreach ($currentPlayer['creatures'] as &$creature) {
            // Check for evolution triggers
            $this->checkForEvolution($creature, $game);
            
            // Process procedural modifiers
            $this->processProceduralModifiers($creature, $game);
        }
    }

    private function processCombatPhase(array &$game): void
    {
        // Combat resolution, damage calculation
        // For now, just log that combat phase occurred
        error_log("Combat phase processed for game " . $game['id']);
    }

    private function processEndPhase(array &$game): void
    {
        // End of turn cleanup, persistent effects
        $currentPlayer = &$game['players'][$game['currentPlayerIndex']];
        
        // Regenerate resources
        $currentPlayer['energy'] = min($currentPlayer['energy'] + 1, 10);
        $currentPlayer['dnaPoints'] = min($currentPlayer['dnaPoints'] + 1, 10);
    }

    private function nextTurn(array &$game): void
    {
        $game['currentPlayerIndex'] = $game['currentPlayerIndex'] === 0 ? 1 : 0;
        $game['turn']++;
    }

    private function checkForEvolution(array &$creature, array &$game): void
    {
        if ($creature['isEvolved']) {
            return; // Already evolved
        }

        $appliedMutationTypes = array_map(
            fn($mutation) => $mutation['mutationType'], 
            $creature['appliedMutations']
        );

        // Get all available evolutions
        $evolutions = SampleCards::getEvolutions();
        
        foreach ($evolutions as $evolution) {
            if ($evolution->baseCreatureType === $creature['baseCard']['id']) {
                // Check if all required mutations are present
                $hasAllMutations = true;
                foreach ($evolution->requiredMutations as $requiredMutation) {
                    if (!in_array($requiredMutation, $appliedMutationTypes)) {
                        $hasAllMutations = false;
                        break;
                    }
                }

                if ($hasAllMutations) {
                    $this->evolveCreature($creature, $evolution, $game);
                    break;
                }
            }
        }
    }

    private function evolveCreature(array &$creature, EvolutionCard $evolution, array &$game): void
    {
        // Apply evolution
        $creature['evolutionHistory'][] = $evolution->toArray();
        $creature['currentStats'] = $evolution->evolvedStats->toArray();
        $creature['activeAbilities'] = array_merge(
            $creature['activeAbilities'], 
            $evolution->evolvedAbilities
        );
        $creature['isEvolved'] = true;

        // Generate procedural traits
        $proceduralTrait = $this->generateProceduralTrait($evolution, $game);
        if ($proceduralTrait) {
            $creature['proceduralTraits'][] = $proceduralTrait;
        }

        error_log("Creature evolved to " . $evolution->name . " in game " . $game['id']);
    }

    private function processProceduralModifiers(array &$creature, array &$game): void
    {
        foreach ($creature['appliedMutations'] as $mutation) {
            if (!empty($mutation['proceduralModifiers'])) {
                foreach ($mutation['proceduralModifiers'] as $modifier) {
                    // Roll for procedural effects
                    if (isset($modifier['effect']['poison_chance'])) {
                        if (rand(1, 100) <= ($modifier['effect']['poison_chance'] * 100)) {
                            if (!in_array('poison', $creature['activeAbilities'])) {
                                $creature['activeAbilities'][] = 'poison';
                                error_log("Creature gained poison ability through procedural modifier");
                            }
                        }
                    }
                }
            }
        }
    }

    private function generateProceduralTrait(EvolutionCard $evolution, array &$game): ?array
    {
        // Generate random procedural traits based on environment and evolution
        $possibleTraits = [
            [
                'id' => 'enhanced_' . uniqid(),
                'name' => 'Enhanced Evolution',
                'description' => 'This evolution gained unexpected enhancements',
                'effect' => ['stat_bonus' => rand(1, 2)]
            ],
            [
                'id' => 'adaptive_' . uniqid(),
                'name' => 'Adaptive Trait',
                'description' => 'Evolved to better suit the environment',
                'effect' => ['environmental_bonus' => 0.1]
            ]
        ];

        // 30% chance to gain a procedural trait
        if (rand(1, 100) <= 30) {
            return $possibleTraits[array_rand($possibleTraits)];
        }

        return null;
    }

    private function getInitialHand(): array
    {
        $allCards = SampleCards::getAllCards();
        $hand = [];
        
        // Give each player a mix of cards
        $cardTypes = ['base_creature', 'mutation', 'base_creature', 'mutation', 'base_creature'];
        
        foreach ($cardTypes as $type) {
            $availableCards = array_filter($allCards, fn($card) => $card->type === $type);
            if (!empty($availableCards)) {
                $hand[] = $availableCards[array_rand($availableCards)]->toArray();
            }
        }
        
        return $hand;
    }

    private function getShuffledDeck(): array
    {
        $allCards = SampleCards::getAllCards();
        $deck = array_map(fn($card) => $card->toArray(), $allCards);
        shuffle($deck);
        return array_slice($deck, 5); // Remove initial hand cards
    }



    public function updateCreature(string $gameId, string $playerId, string $creatureId, array $updates): array
    {
        if (!isset($this->gameStates[$gameId])) {
            throw new \Exception('Game not found');
        }

        $game = &$this->gameStates[$gameId];
        $player = null;
        foreach ($game['players'] as &$p) {
            if ($p['id'] === $playerId) {
                $player = &$p;
                break;
            }
        }

        if (!$player) {
            throw new \Exception('Player not found');
        }

        foreach ($player['creatures'] as &$creature) {
            if ($creature['id'] === $creatureId) {
                $creature = array_merge($creature, $updates);
                break;
            }
        }

        return $game;
    }

    private function getStorageDir(): string
    {
        $storageDir = __DIR__ . '/../../storage/games';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        return $storageDir;
    }

    private function getGameFilePath(string $gameId): string
    {
        return $this->getStorageDir() . '/' . $gameId . '.json';
    }

    private function saveGameState(string $gameId): void
    {
        if (isset($this->gameStates[$gameId])) {
            $filePath = $this->getGameFilePath($gameId);
            file_put_contents($filePath, json_encode($this->gameStates[$gameId], JSON_PRETTY_PRINT));
        }
    }

    private function loadGameState(string $gameId): ?array
    {
        $filePath = $this->getGameFilePath($gameId);
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $gameState = json_decode($content, true);
            if ($gameState) {
                $this->gameStates[$gameId] = $gameState;
                return $gameState;
            }
        }
        return null;
    }

    private function loadGameStates(): void
    {
        $storageDir = $this->getStorageDir();
        $files = glob($storageDir . '/*.json');
        foreach ($files as $file) {
            $gameId = basename($file, '.json');
            $this->loadGameState($gameId);
        }
    }

    public function getGame(string $gameId): ?array
    {
        // Try to load from memory first, then from disk
        if (!isset($this->gameStates[$gameId])) {
            $this->loadGameState($gameId);
        }
        return $this->gameStates[$gameId] ?? null;
    }

    // Override methods to save state after modifications
    public function createGame(string $gameId): array
    {
        $gameState = $this->createGameInternal($gameId);
        $this->saveGameState($gameId);
        return $gameState;
    }

    public function processPhase(string $gameId): array
    {
        $gameState = $this->processPhaseInternal($gameId);
        $this->saveGameState($gameId);
        return $gameState;
    }

    public function summonCreature(string $gameId, string $playerId, string $cardId, array $position): array
    {
        $gameState = $this->summonCreatureInternal($gameId, $playerId, $cardId, $position);
        $this->saveGameState($gameId);
        return $gameState;
    }

    public function applyMutation(string $gameId, string $playerId, string $creatureId, string $mutationCardId): array
    {
        $gameState = $this->applyMutationInternal($gameId, $playerId, $creatureId, $mutationCardId);
        $this->saveGameState($gameId);
        return $gameState;
    }

    // Rename original methods to internal versions
    private function createGameInternal(string $gameId): array
    {
        $this->gameStates[$gameId] = [
            'id' => $gameId,
            'players' => [
                [
                    'id' => 'player1',
                    'name' => 'Player 1',
                    'health' => 20,
                    'dnaPoints' => 3,
                    'energy' => 3,
                    'hand' => $this->getInitialHand(),
                    'deck' => $this->getShuffledDeck(),
                    'creatures' => []
                ],
                [
                    'id' => 'player2',
                    'name' => 'Player 2',
                    'health' => 20,
                    'dnaPoints' => 3,
                    'energy' => 3,
                    'hand' => $this->getInitialHand(),
                    'deck' => $this->getShuffledDeck(),
                    'creatures' => []
                ]
            ],
            'currentPlayerIndex' => 0,
            'turn' => 1,
            'phase' => 'main',
            'battlefield' => [
                'environment' => null,
                'effects' => []
            ]
        ];

        return $this->gameStates[$gameId];
    }

    private function processPhaseInternal(string $gameId): array
    {
        if (!isset($this->gameStates[$gameId])) {
            // Try to load from disk
            if (!$this->loadGameState($gameId)) {
                throw new \Exception('Game not found');
            }
        }

        $game = &$this->gameStates[$gameId];
        
        switch ($game['phase']) {
            case 'main':
                $this->processMainPhase($game);
                $game['phase'] = 'mutation';
                break;
                
            case 'mutation':
                $this->processMutationPhase($game);
                $game['phase'] = 'combat';
                break;
                
            case 'combat':
                $this->processCombatPhase($game);
                $game['phase'] = 'end';
                break;
                
            case 'end':
                $this->processEndPhase($game);
                $game['phase'] = 'main';
                $this->nextTurn($game);
                break;
        }

        return $game;
    }

    private function summonCreatureInternal(string $gameId, string $playerId, string $cardId, array $position): array
    {
        if (!isset($this->gameStates[$gameId])) {
            if (!$this->loadGameState($gameId)) {
                throw new \Exception('Game not found');
            }
        }

        $game = &$this->gameStates[$gameId];
        
        // Validate it's the current player's turn
        if ($game['players'][$game['currentPlayerIndex']]['id'] !== $playerId) {
            throw new \Exception('Not your turn');
        }

        // Validate current phase allows summoning
        if ($game['phase'] !== 'main') {
            throw new \Exception('Cannot summon creatures during ' . $game['phase'] . ' phase');
        }

        $player = &$game['players'][$game['currentPlayerIndex']];
        
        // Find the card in player's hand
        $cardIndex = -1;
        $card = null;
        foreach ($player['hand'] as $index => $handCard) {
            if ($handCard['id'] === $cardId) {
                $cardIndex = $index;
                $card = $handCard;
                break;
            }
        }

        if (!$card) {
            throw new \Exception('Card not found in hand');
        }

        // Validate card type
        if ($card['type'] !== 'base_creature') {
            throw new \Exception('Only base creatures can be summoned');
        }

        // Validate energy cost
        if ($player['energy'] < $card['cost']) {
            throw new \Exception('Not enough energy. Required: ' . $card['cost'] . ', Available: ' . $player['energy']);
        }

        // Validate position (basic bounds checking)
        if ($position['x'] < 0 || $position['x'] > 800 || $position['y'] < 0 || $position['y'] > 200) {
            throw new \Exception('Invalid position');
        }

        // Check for creature overlap (simplified)
        foreach ($player['creatures'] as $existingCreature) {
            if (isset($existingCreature['position'])) {
                $distance = sqrt(
                    pow($position['x'] - $existingCreature['position']['x'], 2) + 
                    pow($position['y'] - $existingCreature['position']['y'], 2)
                );
                if ($distance < 100) { // Minimum distance between creatures
                    throw new \Exception('Cannot place creature too close to existing creature');
                }
            }
        }

        // Create creature instance
        $creature = [
            'id' => 'creature_' . uniqid(),
            'baseCard' => $card,
            'currentStats' => $card['stats'],
            'appliedMutations' => [],
            'evolutionHistory' => [],
            'activeAbilities' => [],
            'proceduralTraits' => [],
            'isEvolved' => false,
            'position' => $position
        ];

        // Execute the summon
        $player['creatures'][] = $creature;
        array_splice($player['hand'], $cardIndex, 1); // Remove card from hand
        $player['energy'] -= $card['cost']; // Deduct energy

        error_log("Creature summoned: {$card['name']} by {$playerId} in game {$gameId}");

        return $game;
    }

    private function applyMutationInternal(string $gameId, string $playerId, string $creatureId, string $mutationCardId): array
    {
        if (!isset($this->gameStates[$gameId])) {
            if (!$this->loadGameState($gameId)) {
                throw new \Exception('Game not found');
            }
        }

        $game = &$this->gameStates[$gameId];
        
        // Validate it's the current player's turn
        if ($game['players'][$game['currentPlayerIndex']]['id'] !== $playerId) {
            throw new \Exception('Not your turn');
        }

        // Validate current phase allows mutations
        if ($game['phase'] !== 'main' && $game['phase'] !== 'mutation') {
            throw new \Exception('Cannot apply mutations during ' . $game['phase'] . ' phase');
        }

        $player = &$game['players'][$game['currentPlayerIndex']];
        
        // Find the mutation card in player's hand
        $cardIndex = -1;
        $mutationCard = null;
        foreach ($player['hand'] as $index => $handCard) {
            if ($handCard['id'] === $mutationCardId) {
                $cardIndex = $index;
                $mutationCard = $handCard;
                break;
            }
        }

        if (!$mutationCard) {
            throw new \Exception('Mutation card not found in hand');
        }

        // Validate card type
        if ($mutationCard['type'] !== 'mutation') {
            throw new \Exception('Card is not a mutation');
        }

        // Validate DNA points cost
        if ($player['dnaPoints'] < $mutationCard['cost']) {
            throw new \Exception('Not enough DNA points. Required: ' . $mutationCard['cost'] . ', Available: ' . $player['dnaPoints']);
        }

        // Find the target creature
        $creature = null;
        $creatureIndex = -1;
        foreach ($player['creatures'] as $index => &$c) {
            if ($c['id'] === $creatureId) {
                $creature = &$c;
                $creatureIndex = $index;
                break;
            }
        }

        if (!$creature) {
            throw new \Exception('Target creature not found');
        }

        // Validate DNA slot compatibility
        $targetSlot = $mutationCard['targetSlot'];
        if (!in_array($targetSlot, $creature['baseCard']['dnaSlots'])) {
            throw new \Exception('Creature does not have compatible DNA slot: ' . $targetSlot);
        }

        // Check mutation limit
        if (count($creature['appliedMutations']) >= $creature['baseCard']['maxMutations']) {
            throw new \Exception('Creature has reached maximum mutations (' . $creature['baseCard']['maxMutations'] . ')');
        }

        // Check for duplicate mutation types
        foreach ($creature['appliedMutations'] as $existingMutation) {
            if ($existingMutation['mutationType'] === $mutationCard['mutationType']) {
                throw new \Exception('Creature already has this mutation type');
            }
        }

        // Apply the mutation
        $creature['appliedMutations'][] = $mutationCard;
        
        // Apply stat changes
        if (isset($mutationCard['primaryEffect']['statChanges'])) {
            foreach ($mutationCard['primaryEffect']['statChanges'] as $stat => $value) {
                if (isset($creature['currentStats'][$stat])) {
                    $creature['currentStats'][$stat] += $value;
                }
            }
        }

        // Add abilities
        if (isset($mutationCard['primaryEffect']['abilities'])) {
            $creature['activeAbilities'] = array_merge(
                $creature['activeAbilities'],
                $mutationCard['primaryEffect']['abilities']
            );
        }

        // Execute the mutation
        array_splice($player['hand'], $cardIndex, 1); // Remove card from hand
        $player['dnaPoints'] -= $mutationCard['cost']; // Deduct DNA points

        error_log("Mutation applied: {$mutationCard['name']} to creature {$creatureId} by {$playerId} in game {$gameId}");

        return $game;
    }
}