<?php

declare(strict_types = 1);

namespace Tests\Implementation;

use MightyBattle\GameQueue\Entity\PlayedGameInterface;
use MightyBattle\GameQueue\Entity\PlayerInterface;
use MightyBattle\GameQueue\Util\ImmutableTypedList;

class PlayedGame implements PlayedGameInterface
{
    private ImmutableTypedList $players;

    public function __construct(PlayerInterface $loser, PlayerInterface $winner)
    {
        $this->players = new ImmutableTypedList(PlayerInterface::class, [$loser, $winner]);
    }

    public function players(): ImmutableTypedList
    {
        return $this->players;
    }

    public function winner(): PlayerInterface
    {
        return $this->players->last();
    }
}
