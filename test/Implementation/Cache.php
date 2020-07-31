<?php

declare(strict_types = 1);

namespace Tests\Implementation;

use MightyBattle\GameQueue\Cache\CacheInterface;

class Cache implements CacheInterface
{
    private array $values = [];

    public function set(string $key, array $value): void
    {
        $this->values[$key] = $value;
    }

    public function getOrDefault(string $key, array $default = []): array
    {
        return $this->values[$key] ?? $default;
    }

    public function remove(string $key): void
    {
        unset($this->values[$key]);
    }
}
