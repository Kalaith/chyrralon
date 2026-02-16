import React, { useState } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import { RootState } from '../store/store';
import { nextPhase, updateGameState } from '../store/gameSlice';
import { GameAPI } from '../services/api';
import { Card } from './Card';
import { CreatureInstance } from './CreatureInstance';
import { GameCard, CreatureInstance as CreatureType } from '../types/cards';
import './GameBoard.css';

export const GameBoard: React.FC = () => {
  const dispatch = useDispatch();
  const gameState = useSelector((state: RootState) => state.game);
  const [selectedCard, setSelectedCard] = useState<GameCard | null>(null);
  const [selectedCreature, setSelectedCreature] = useState<CreatureType | null>(null);

  const currentPlayer = gameState.players[gameState.currentPlayerIndex];
  const opponent = gameState.players[1 - gameState.currentPlayerIndex];

  const handleCardClick = (card: GameCard) => {
    setSelectedCard(selectedCard?.id === card.id ? null : card);
    setSelectedCreature(null);
  };

  const handleCreatureClick = (creature: CreatureType) => {
    setSelectedCreature(selectedCreature?.id === creature.id ? null : creature);
    setSelectedCard(null);
  };

  const handleBattlefieldClick = async (event: React.MouseEvent<HTMLDivElement>) => {
    if (!selectedCard || selectedCard.type !== 'base_creature') return;

    const rect = event.currentTarget.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;

    try {
      // Send to server for validation and execution
      const updatedGameState = await GameAPI.summonCreature(gameState.id, {
        playerId: currentPlayer.id,
        cardId: selectedCard.id,
        position: { x, y },
      });

      // Update game state with server response
      dispatch(updateGameState(updatedGameState));
      setSelectedCard(null);
    } catch (error: unknown) {
      console.error('Failed to summon creature:', error);
      alert('Cannot summon creature: ' + getErrorMessage(error));
    }
  };

  const handleMutationApply = async () => {
    if (!selectedCard || !selectedCreature || selectedCard.type !== 'mutation') return;

    try {
      // Send to server for validation and execution
      const updatedGameState = await GameAPI.applyMutation(gameState.id, {
        playerId: currentPlayer.id,
        creatureId: selectedCreature.id,
        mutationCardId: selectedCard.id,
      });

      // Update game state with server response
      dispatch(updateGameState(updatedGameState));
      setSelectedCard(null);
      setSelectedCreature(null);
    } catch (error: unknown) {
      console.error('Failed to apply mutation:', error);
      alert('Cannot apply mutation: ' + getErrorMessage(error));
    }
  };

  const handleNextPhase = async () => {
    // Update phase locally first for immediate feedback
    dispatch(nextPhase());

    try {
      // Process phase on server
      const updatedGameState = await GameAPI.processPhase(gameState.id);

      // Update game state with server response (includes procedural effects, evolutions, etc.)
      dispatch(updateGameState(updatedGameState));
    } catch (error) {
      console.error('Failed to process phase on server:', error);
      // Could revert local change or show error message
    }
  };

  const canPlayCard = (card: GameCard): boolean => {
    if (card.type === 'base_creature') {
      return currentPlayer.energy >= card.cost;
    }
    if (card.type === 'mutation') {
      return currentPlayer.dnaPoints >= card.cost && selectedCreature !== null;
    }
    return false;
  };

  return (
    <div className="game-board">
      <div className="opponent-area">
        <div className="player-info">
          <h3>{opponent.name}</h3>
          <div className="resources">
            <span>Health: {opponent.health}</span>
            <span>Energy: {opponent.energy}</span>
            <span>DNA: {opponent.dnaPoints}</span>
          </div>
        </div>
        <div className="battlefield opponent-battlefield">
          {opponent.creatures.map(creature => (
            <div
              key={creature.id}
              className="creature-position"
              style={{
                left: creature.position?.x || 0,
                top: creature.position?.y || 0,
              }}
            >
              <CreatureInstance creature={creature} />
            </div>
          ))}
        </div>
      </div>

      <div className="center-area">
        <div className="game-info">
          <div className="turn-info">
            Turn {gameState.turn} - {gameState.phase}
          </div>
          <div className="current-player">Current Player: {currentPlayer.name}</div>
          {gameState.battlefield.environment && (
            <div className="environment">Environment: {gameState.battlefield.environment.name}</div>
          )}
        </div>

        <div className="action-buttons">
          {selectedCard && selectedCreature && selectedCard.type === 'mutation' && (
            <button onClick={handleMutationApply} className="action-button">
              Apply Mutation
            </button>
          )}
          <button onClick={handleNextPhase} className="action-button">
            Next Phase
          </button>
        </div>
      </div>

      <div className="player-area">
        <div className="battlefield player-battlefield" onClick={handleBattlefieldClick}>
          {currentPlayer.creatures.map(creature => (
            <div
              key={creature.id}
              className="creature-position"
              style={{
                left: creature.position?.x || 0,
                top: creature.position?.y || 0,
              }}
            >
              <CreatureInstance
                creature={creature}
                onClick={() => handleCreatureClick(creature)}
                isSelected={selectedCreature?.id === creature.id}
              />
            </div>
          ))}
          {selectedCard && selectedCard.type === 'base_creature' && (
            <div className="summon-hint">Click to summon {selectedCard.name}</div>
          )}
        </div>

        <div className="player-info">
          <h3>{currentPlayer.name}</h3>
          <div className="resources">
            <span>Health: {currentPlayer.health}</span>
            <span>Energy: {currentPlayer.energy}</span>
            <span>DNA: {currentPlayer.dnaPoints}</span>
          </div>
        </div>

        <div className="hand">
          {currentPlayer.hand.map(card => (
            <Card
              key={card.id}
              card={card}
              onClick={() => handleCardClick(card)}
              isPlayable={canPlayCard(card)}
              isSelected={selectedCard?.id === card.id}
            />
          ))}
        </div>
      </div>
    </div>
  );
};
const getErrorMessage = (error: unknown): string => {
  if (error instanceof Error) {
    return error.message;
  }
  return 'Unknown error';
};
