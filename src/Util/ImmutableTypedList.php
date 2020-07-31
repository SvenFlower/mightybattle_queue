<?php

declare(strict_types=1);

namespace MightyBattle\GameQueue\Util;

class ImmutableTypedList
{
    protected string $className;
    protected array $values = [];
    private bool $isClass = false;
    private bool $isInterface = false;

    public function __construct(string $className, array $values = [])
    {
        if (class_exists($className)) {
            $this->isClass = true;
        } else if (interface_exists($className)) {
            $this->isInterface = true;
        } else {
            throw new \InvalidArgumentException('Typed list need class name or interface name as a validator, got ' . $className);
        }

        $this->className = $className;
        $this->addAll($values);
    }

    protected function addAll(array $values): void
    {
        foreach($values as $value) {
            $this->add($value);
        }
    }

    protected function add(object $value): void
    {
        $this->validate($value);
        $this->values[] = $value;
    }

    private function validate(object $value): void
    {
        if ($this->isClass) {
            if ($value instanceof $this->className) {
                return;
            }
        } else if ($this->isInterface) {
            $interfaces = class_implements(get_class($value));
            if (isset($interfaces[$this->className])) {
                return;
            }
        }

        throw new \InvalidArgumentException();
    }

    public function get(int $index): object
    {
        if (isset($this->values[$index])) {
            return $this->values[$index];
        }

        throw new \OutOfBoundsException($index);
    }

    public function indexOf(object $value): ?int
    {
        $this->validate($value);
        $key = array_search($value, $this->values, true);
        return $key === false ? null : $key;
    }

    protected function set(int $index, object  $value): void
    {
        $this->values[$index] = $value;
    }

    public function values(): array
    {
        return $this->values;
    }

    protected function isNotEmpty(): void
    {
        if ($this->size() === 0) {
            throw new \OutOfRangeException();
        }
    }

    public function first(): object
    {
        if ($this->size() === 0) {
            throw new \OutOfRangeException();
        }
        return $this->values[0];
    }

    public function last(): object
    {
        if($this->size() === 0) {
            throw new \OutOfRangeException();
        }

        return $this->values[$this->size() - 1];
    }

    public function size(): int
    {
        return count($this->values);
    }
}
