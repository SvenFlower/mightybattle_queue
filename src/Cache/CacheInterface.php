<?php

namespace MightyBattle\GameQueue\Cache;

interface CacheInterface
{
    public function set(string $key, array $value): void;
    public function getOrDefault(string $key, array $default = []): array;
    public function remove(string $key): void;
}
