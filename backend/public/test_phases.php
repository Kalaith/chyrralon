<?php

header('Content-Type: application/json');
require __DIR__ . '/../vendor/autoload.php';

try {
    $gameEngine = \Chyrralon\Services\GameEngine::getInstance();
    
    echo "=== Creating Game ===\n";
    $gameId = 'test_game_' . uniqid();
    $gameState = $gameEngine->createGame($gameId);
    echo "Game created: " . $gameState['id'] . "\n";
    echo "Initial phase: " . $gameState['phase'] . "\n";
    echo "Turn: " . $gameState['turn'] . "\n\n";
    
    echo "=== Processing Phases ===\n";
    for ($i = 0; $i < 8; $i++) {
        $gameState = $gameEngine->processPhase($gameId);
        echo "Phase {$i}: {$gameState['phase']}, Turn: {$gameState['turn']}\n";
        
        // Add some creatures for testing evolution
        if ($i === 0 && empty($gameState['players'][0]['creatures'])) {
            // Simulate adding a creature with mutations
            $testCreature = [
                'id' => 'test_creature_1',
                'baseCard' => [
                    'id' => 'grub_001',
                    'name' => 'Grub'
                ],
                'currentStats' => ['attack' => 5, 'health' => 6, 'armor' => 2],
                'appliedMutations' => [
                    ['mutationType' => 'spikes'],
                    ['mutationType' => 'carapace']
                ],
                'evolutionHistory' => [],
                'activeAbilities' => ['thorns', 'armored'],
                'proceduralTraits' => [],
                'isEvolved' => false
            ];
            $gameState['players'][0]['creatures'][] = $testCreature;
            $gameEngine->updateCreature($gameId, 'player1', 'test_creature_1', $testCreature);
            echo "  - Added test creature with Spikes + Carapace mutations\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}