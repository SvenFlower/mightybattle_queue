<?php

declare (strict_types=1);

namespace MightyBattle\GameQueue\Entity;

interface PlayerInterface
{
    public function id(): string;

    public function deck(): DeckInterface;

    // TODO: use it
    public function playedGamesCount(): int;

    // TODO: use it
    public function wonGamesCount(): int;

    public function level(): int;
}
