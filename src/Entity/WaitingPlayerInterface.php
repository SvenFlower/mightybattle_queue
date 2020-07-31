<?php

namespace MightyBattle\GameQueue\Entity;

interface WaitingPlayerInterface extends PlayerInterface
{
    public function waitingSince(): \DateTimeInterface;
}
