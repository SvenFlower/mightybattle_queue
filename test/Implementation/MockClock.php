<?php

declare(strict_types = 1);

namespace Tests\Implementation;

use MightyBattle\GameQueue\Util\Clock;

class MockClock extends Clock
{
    private \DateTimeImmutable $now;

    public function setNow(\DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    public function getNow(): \DateTimeImmutable
    {
        return $this->now;
    }
}
