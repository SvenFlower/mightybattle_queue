<?php

declare(strict_types=1);

namespace MightyBattle\GameQueue\Match;

use MightyBattle\GameQueue\Entity\WaitingPlayerInterface;

class MatchChance
{
    private WaitingPlayerInterface $waitingPlayer;
    private float $levelChance;
    private float $cardsCountChance;
    private float $deckPointsChance;

    public function __construct(WaitingPlayerInterface $waitingPlayer, float $levelChance, float $cardsCountChance, float $deckPointsChance)
    {
        $this->waitingPlayer = $waitingPlayer;
        $this->levelChance = $levelChance;
        $this->cardsCountChance = $cardsCountChance;
        $this->deckPointsChance = $deckPointsChance;
    }

    /**
     * @return WaitingPlayerInterface
     */
    public function getWaitingPlayer(): WaitingPlayerInterface
    {
        return $this->waitingPlayer;
    }

    /**
     * @return float
     */
    public function getLevelChance(): float
    {
        return $this->levelChance;
    }

    /**
     * @return float
     */
    public function getCardsCountChance(): float
    {
        return $this->cardsCountChance;
    }

    /**
     * @return float
     */
    public function getDeckPointsChance(): float
    {
        return $this->deckPointsChance;
    }

    public function chance(): float
    {
        return ($this->getCardsCountChance() + $this->getDeckPointsChance() + $this->getLevelChance()) / 3;
    }
}
