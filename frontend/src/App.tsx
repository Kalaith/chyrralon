import React, { useEffect } from 'react';
import { useDispatch } from 'react-redux';
import { updateGameState, initializeGame } from './store/gameSlice';
import { GameAPI } from './services/api';
import { GameBoard } from './components/GameBoard';
import { sampleCards } from './data/sampleCards';
import './App.css';

function App() {
  const dispatch = useDispatch();

  useEffect(() => {
    // Create a new server-side game session
    const initializeServerGame = async () => {
      try {
        const gameState = await GameAPI.createGame();
        dispatch(updateGameState(gameState));
      } catch (error) {
        console.error('Failed to create server-side game:', error);
        // Fallback to local initialization
        dispatch(
          initializeGame({
            gameId: 'game_' + Date.now(),
            cards: sampleCards,
          })
        );
      }
    };

    initializeServerGame();
  }, [dispatch]);

  return (
    <div className="App">
      <GameBoard />
    </div>
  );
}

export default App;
