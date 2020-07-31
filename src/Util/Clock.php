<?php

namespace MightyBattle\GameQueue\Util;

class Clock
{
    private static Clock $instance;

    private static function getInstance(): Clock
    {
        if (self::$instance === null) {
            self::$instance = new Clock();
        }

        return self::$instance;
    }

    public static function setInstance(Clock $clock): void
    {
        self::$instance = $clock;
    }

    public function getNow(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public static function now(): \DateTimeImmutable
    {
        return self::getInstance()->getNow();
    }
}
