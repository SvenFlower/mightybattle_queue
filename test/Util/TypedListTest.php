<?php

namespace Tests\Util;

use MightyBattle\GameQueue\Util\ImmutableTypedList;
use MightyBattle\GameQueue\Util\TypedList;
use PHPUnit\Framework\TestCase;
use Tests\TestData\SomeClass;
use Tests\TestData\SomeInterface;

/**
 * @covers ImmutableTypedList
 */
class TypedListTest extends TestCase
{
    public function testConstructorFailsWithNonClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ImmutableTypedList('');
    }

    public function testConstructorFailsWithNonExistingClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ImmutableTypedList('\\Dummy\\SomeClassName');
    }

    public function testConstructorPassesWithAnInterface(): void
    {
        new ImmutableTypedList(SomeInterface::class);
        $this->addToAssertionCount(1);
    }

    public function testAddFailsWithNonObjectValue(): void
    {
        $list = new TypedList(SomeInterface::class);
        $this->expectException(\TypeError::class);
        $list->add(1);
    }

    public function testAddAllFailsWithNonObjectValue(): void
    {
        $list = new TypedList(SomeInterface::class);
        $this->expectException(\TypeError::class);
        $list->addAll([1]);
    }

    public function testAddFailsWithDifferentClassInterfaces(): void
    {
        $list = new TypedList(SomeInterface::class);
        $this->expectException(\InvalidArgumentException::class);
        $list->add(new class {});
    }

    public function testValidatesInterface(): void
    {
        $list = new TypedList(SomeInterface::class);
        $list->add($value = new class implements SomeInterface {
            public function id(): int
            {
                return 1;
            }
        });

        $this->assertSame($value, $list->get(0));
    }

    public function testValidatesClass(): void
    {
        $list = new TypedList(SomeClass::class);
        $list->add($value = new SomeClass('myName'));
        $this->assertSame($value, $list->get(0));
    }

    public function testAddFailsWithDifferentClass(): void
    {
        $list = new TypedList(SomeClass::class);
        $this->expectException(\InvalidArgumentException::class);
        $list->add(new class {});
    }
}
