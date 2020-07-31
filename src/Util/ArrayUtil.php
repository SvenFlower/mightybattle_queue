<?php

declare(strict_types=1);

namespace MightyBattle\GameQueue\Util;

class ArrayUtil
{
    public static function findOrDefault(array $arr, \Closure $predicate, $default)
    {
        foreacH($arr as $value) {
            if ($predicate($value)) {
                return $value;
            }
        }

        return $default;
    }

    public static function first(array $items)
    {
        return $items[array_key_first($items)];
    }

    public static function last(array $items)
    {
        return $items[array_key_last($items)];
    }
}
