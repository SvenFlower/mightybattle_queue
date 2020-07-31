<?php

declare(strict_types=1);

namespace MightyBattle\GameQueue\Util;

class Map implements \ArrayAccess, \Iterator
{
    private array $map = [];
    private ?string $position = null;
    private int $intPosition = 0;

    public function set(string $id, $value): void
    {
        $this->map[$id] = $value;
    }

    public function get($id)
    {
        return $this->map[$id] ?? null;
    }

    public function getOrDefault($id, $default)
    {
        return $this->map[$id] ?? $default;
    }

    public function has($id): bool
    {
        return isset($this->map[$id]);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->map[$offset]);
    }

    public function current()
    {
        return $this->get($this->position);
    }

    public function next(): void
    {
        ++$this->intPosition;
        $keys = array_keys($this->map);
        if (!isset($keys[$this->intPosition])) {
            return;
        }
        $this->position = $keys[$this->intPosition];
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return $this->intPosition >= 0 && \count($this->map) > $this->intPosition;
    }

    public function rewind(): void
    {
        $keys = array_keys($this->map);
        if (empty($this->map) || !isset($keys[0])) {
            $this->position = null;
            $this->intPosition = -1;

            return;
        }

        $this->position = $keys[0];
        $this->intPosition = 0;
    }

    public function forEach(\Closure $closure): void
    {
        foreach ($this->map as $key => $value) {
            $closure($key, $value);
        }
    }
}
