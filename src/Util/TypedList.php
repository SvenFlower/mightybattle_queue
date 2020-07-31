<?php

declare(strict_types=1);

namespace MightyBattle\GameQueue\Util;

class TypedList extends ImmutableTypedList
{
    public function set(int $index, object  $value): void
    {
        parent::set($index, $value);
    }

    public function add(object  $value): void
    {
        parent::add($value);
    }

    public function addAll(array $values): void
    {
        parent::addAll($values);
    }

    public function remove(int $index): void
    {
        unset($this->values[$index]);
    }

    public function sort(callable $cmpFunc): TypedList
    {
        $data = [...$this->values];
        usort($data, $cmpFunc);
        return new TypedList($this->className, $data);
    }

    public function isEmpty(): bool
    {
        return $this->size() === 0;
    }

    public function filter(\Closure $closure, bool $self = false): TypedList
    {
        $values = array_filter($this->values(), $closure);
        if ($self) {
            $this->values = $values;
            return $this;
        }

        return new TypedList($this->className, $values);
    }

    public function duplicate(): TypedList
    {
        return new TypedList($this->className, $this->values());
    }

    public function slice(int $start): TypedList
    {
        return new TypedList($this->className, array_slice($this->values(), $start));
    }
}
