# Chyrralon - Mutation & Evolution Digital CCG

A digital collectible card game where players summon base creatures and guide their growth through mutations and evolutions.

## Project Structure

```
chyrralon/
├── frontend/          # React/TypeScript frontend
├── backend/           # PHP backend API
└── GDD.md            # Game Design Document
```

## Features Implemented

### Core Systems
- ✅ Card data models (Base Creatures, Mutations, Evolutions, Spells, Environment)
- ✅ Game state management with Redux Toolkit
- ✅ Card rendering system with dynamic art layering
- ✅ Game board and battlefield UI
- ✅ Basic creature summoning and mutation system

### Card Types
- **Base Creatures**: Starting units with DNA slots for mutations
- **Mutation Cards**: Permanent upgrades that modify creature stats and abilities
- **Evolution Cards**: Advanced forms triggered by specific mutation combinations
- **Spell Cards**: One-time effects
- **Environment Cards**: Battlefield modifiers

### Game Mechanics
- DNA Points system for mutations
- Energy system for summoning creatures
- Turn-based phases (Setup, Main, Mutation, Combat, End)
- Dynamic creature stat tracking
- Procedural modifier system (foundation)

## Running the Project

### Backend (PHP)

1. Navigate to the backend directory:
   ```bash
   cd backend
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Start the PHP development server:
   ```bash
   php -S localhost:8000 -t public
   ```

The API will be available at `http://localhost:8000`

### Frontend (React)

1. Navigate to the frontend directory:
   ```bash
   cd frontend
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Start the development server:
   ```bash
   npm start
   ```

The application will open at `http://localhost:3000`

## API Endpoints

- `GET /api/health` - Health check
- `GET /api/cards` - Get all available cards
- `POST /api/game/create` - Create new game session

## Sample Cards

The game includes sample cards:
- **Grub**: Basic creature (1/3, DNA slots: body, attack, defense)
- **Spore**: Fungal creature (0/2, DNA slots: essence, defense, mind)
- **Spikes**: Mutation (+2 attack, thorns ability)
- **Carapace**: Mutation (+3 armor, +1 health, armored ability)
- **Beetle Warrior**: Evolution (requires Spikes + Carapace on Grub)

## Game Flow

1. Players start with 20 health, 3 energy, 3 DNA points
2. Draw initial hand of 5 cards
3. Summon base creatures using energy
4. Apply mutations using DNA points
5. Creatures evolve when mutation requirements are met
6. Combat and strategic gameplay

## Next Steps

Still to implement:
- Complete mutation and evolution logic
- DNA slot validation system
- Procedural modifier generation
- Combat system
- Win conditions
- Real-time multiplayer support
- Advanced art layering system

## License

This project is licensed under the MIT License - see the individual component README files for details.

Part of the WebHatchery game collection.