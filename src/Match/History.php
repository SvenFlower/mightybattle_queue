<?php

declare(strict_types=1);

namespace MightyBattle\GameQueue\Match;

class History
{
    private int $lowValue;
    private int $highValue;
    private int $won;
    private int $lost;

    public function __construct(int $lowValue, int $highValue, int $won, int $lost)
    {
        $this->lowValue = $lowValue;
        $this->highValue = $highValue;
        $this->won = $won;
        $this->lost = $lost;
    }

    public function getLowValue(): int
    {
        return $this->lowValue;
    }

    public function getHighValue(): int
    {
        return $this->highValue;
    }

    public function getWon(): int
    {
        return $this->won;
    }

    public function getLost(): int
    {
        return $this->lost;
    }

    public function increaseWon(): void
    {
        ++$this->won;
    }

    public function increaseLost(): void
    {
        ++$this->lost;
    }

    public function toArray(): array
    {
        return [
            'low' => $this->lowValue,
            'high' => $this->highValue,
            'won' => $this->won,
            'lost' => $this->lost,
        ];
    }
}
