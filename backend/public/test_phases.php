<?php

header('Content-Type: application/json');

$autoloadCandidates = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

$autoloadPath = null;
foreach ($autoloadCandidates as $candidate) {
    if (file_exists($candidate)) {
        $autoloadPath = $candidate;
        break;
    }
}

if ($autoloadPath === null) {
    throw new RuntimeException('Composer autoload.php not found from ' . __DIR__);
}

$loader = require $autoloadPath;
$projectSrc = realpath(__DIR__ . '/../src');
if ($projectSrc !== false && $loader instanceof \Composer\Autoload\ClassLoader) {
    $loader->addPsr4('Chyrralon\\', $projectSrc . DIRECTORY_SEPARATOR, true);
}

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
