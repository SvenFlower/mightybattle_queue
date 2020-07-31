<?php

declare(strict_types=1);

namespace Tests\Implementation;

use MightyBattle\GameQueue\Entity\DeckInterface;

class Deck implements DeckInterface
{
    private int $cardsCount;
    private int $cardsPoints;

    public function __construct(int $cardsCount, int $cardsPoints)
    {
        $this->cardsCount = $cardsCount;
        $this->cardsPoints = $cardsPoints;
    }

    public function cardsCount(): int
    {
        return $this->cardsCount;
    }

    public function cardsPoints(): int
    {
        return $this->cardsPoints;
    }
}
