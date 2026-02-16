import React from 'react';
import { GameCard, CardType, BaseCreatureCard, MutationCard } from '../types/cards';
import './Card.css';

interface CardProps {
  card: GameCard;
  onClick?: () => void;
  isPlayable?: boolean;
  isSelected?: boolean;
}

export const Card: React.FC<CardProps> = ({
  card,
  onClick,
  isPlayable = false,
  isSelected = false,
}) => {
  const getCardTypeColor = (type: CardType): string => {
    switch (type) {
      case CardType.BaseCreature:
        return '#4CAF50';
      case CardType.Mutation:
        return '#FF9800';
      case CardType.Evolution:
        return '#9C27B0';
      case CardType.Spell:
        return '#2196F3';
      case CardType.Environment:
        return '#795548';
      default:
        return '#666';
    }
  };

  const renderCardContent = () => {
    switch (card.type) {
      case CardType.BaseCreature: {
        const creature = card as BaseCreatureCard;
        return (
          <div className="card-stats">
            <div className="stat-row">
              <span>Attack: {creature.stats.attack}</span>
              <span>Health: {creature.stats.health}</span>
            </div>
            <div className="dna-slots">DNA: {creature.dnaSlots.join(', ')}</div>
          </div>
        );
      }

      case CardType.Mutation: {
        const mutation = card as MutationCard;
        return (
          <div className="mutation-info">
            <div className="target-slot">Slot: {mutation.targetSlot}</div>
            <div className="primary-effect">
              {mutation.primaryEffect.statChanges && (
                <span className="stat-changes">
                  {Object.entries(mutation.primaryEffect.statChanges)
                    .map(([stat, value]) => `+${value} ${stat}`)
                    .join(', ')}
                </span>
              )}
            </div>
          </div>
        );
      }

      default:
        return null;
    }
  };

  return (
    <div
      className={`card ${isPlayable ? 'playable' : ''} ${isSelected ? 'selected' : ''}`}
      onClick={onClick}
      style={{ borderColor: getCardTypeColor(card.type) }}
    >
      <div className="card-header">
        <h3 className="card-name">{card.name}</h3>
        <span className="card-cost">{card.cost}</span>
      </div>

      <div className="card-art">
        {card.artUrl ? (
          <img src={card.artUrl} alt={card.name} />
        ) : (
          <div className="placeholder-art" style={{ backgroundColor: getCardTypeColor(card.type) }}>
            {card.type.replace('_', ' ').toUpperCase()}
          </div>
        )}
      </div>

      <div className="card-description">{card.description}</div>

      {renderCardContent()}
    </div>
  );
};
