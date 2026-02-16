import React from 'react';
import { CreatureInstance as CreatureType } from '../types/cards';
import './CreatureInstance.css';

interface CreatureInstanceProps {
  creature: CreatureType;
  onClick?: () => void;
  isSelected?: boolean;
}

export const CreatureInstance: React.FC<CreatureInstanceProps> = ({
  creature,
  onClick,
  isSelected = false,
}) => {
  const getMutationLayers = () => {
    return creature.appliedMutations.map((mutation, index) => (
      <div
        key={mutation.id}
        className={`mutation-layer ${mutation.mutationType}`}
        style={{ zIndex: index + 1 }}
      >
        {mutation.mutationType}
      </div>
    ));
  };

  return (
    <div className={`creature-instance ${isSelected ? 'selected' : ''}`} onClick={onClick}>
      <div className="creature-art-stack">
        <div className="base-art">
          {creature.baseCard.artUrl ? (
            <img src={creature.baseCard.artUrl} alt={creature.baseCard.name} />
          ) : (
            <div className="placeholder-art">{creature.baseCard.name}</div>
          )}
        </div>
        {getMutationLayers()}
      </div>

      <div className="creature-info">
        <h4 className="creature-name">{creature.baseCard.name}</h4>
        <div className="creature-stats">
          <span className="stat attack">{creature.currentStats.attack}</span>
          <span className="stat health">{creature.currentStats.health}</span>
          {creature.currentStats.armor > 0 && (
            <span className="stat armor">{creature.currentStats.armor}</span>
          )}
        </div>
      </div>

      {creature.appliedMutations.length > 0 && (
        <div className="mutations-list">
          {creature.appliedMutations.map(mutation => (
            <div key={mutation.id} className="mutation-tag">
              {mutation.name}
            </div>
          ))}
        </div>
      )}

      {creature.activeAbilities.length > 0 && (
        <div className="abilities-list">
          {creature.activeAbilities.map(ability => (
            <div key={ability} className="ability-tag">
              {ability}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
