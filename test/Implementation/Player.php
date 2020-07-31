<?php

declare(strict_types=1);

namespace Tests\Implementation;

use MightyBattle\GameQueue\Entity\DeckInterface;
use MightyBattle\GameQueue\Entity\WaitingPlayerInterface;

class Player implements WaitingPlayerInterface
{
    private string $id;
    private DeckInterface $deck;
    private int $playedGamesCount;
    private int $wonGamesCount;
    private int $level;
    private \DateTimeImmutable $waitingSince;

    public function __construct(string $id, DeckInterface $deck, int $playedGamesCount, int $wonGamesCount, int $level, \DateTimeImmutable $waitingSince)
    {
        $this->id = $id;
        $this->deck = $deck;
        $this->playedGamesCount = $playedGamesCount;
        $this->wonGamesCount = $wonGamesCount;
        $this->level = $level;
        $this->waitingSince = $waitingSince;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function deck(): DeckInterface
    {
        return $this->deck;
    }

    public function playedGamesCount(): int
    {
        return $this->playedGamesCount;
    }

    public function wonGamesCount(): int
    {
        return $this->wonGamesCount;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function waitingSince(): \DateTimeInterface
    {
        return $this->waitingSince;
    }
}
