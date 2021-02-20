<?php

namespace MightyBattle\GameQueue\Match;

use MightyBattle\GameQueue\Entity\PlayerInterface;

class QueueMatch
{
    private PlayerInterface $player;
    private PlayerInterface $player2;

    public function __construct(PlayerInterface $player, PlayerInterface $player2)
    {
        $this->player = $player;
        $this->player2 = $player2;
    }

    public function player(): PlayerInterface
    {
        return $this->player;
    }

    public function player2(): PlayerInterface
    {
        return $this->player2;
    }
}
