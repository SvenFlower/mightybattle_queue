<?php

declare(strict_types=1);

namespace MightyBattle\GameQueue\Entity;

interface DeckInterface
{
    public function cardsCount(): int;

    public function cardsPoints(): int;
}
