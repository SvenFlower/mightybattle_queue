<?php

declare(strict_types=1);

namespace MightyBattle\GameQueue\Util;

abstract class NumberUtil
{
    public static function checkDiff(int $base, int $toCompare, int $maxDiff): bool
    {
        $diff = abs($base - $toCompare);
        return $diff <= $maxDiff;
    }
}
