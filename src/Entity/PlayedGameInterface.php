<?php

declare(strict_types=1);

namespace MightyBattle\GameQueue\Entity;

use MightyBattle\GameQueue\Util\ImmutableTypedList;

interface PlayedGameInterface
{
    /**
     * @return ImmutableTypedList<PlayerInterface>
     */
    public function players(): ImmutableTypedList;
    public function winner(): PlayerInterface;
}
