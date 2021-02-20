<?php

declare(strict_types=1);

namespace Tests\Match;

use MightyBattle\GameQueue\Entity\PlayerInterface;
use MightyBattle\GameQueue\Match\QueueMatch;
use MightyBattle\GameQueue\Match\MatchService;
use MightyBattle\GameQueue\Util\Clock;
use MightyBattle\GameQueue\Util\TypedList;
use PHPUnit\Framework\TestCase;
use Tests\Implementation\Cache;
use Tests\Implementation\Deck;
use Tests\Implementation\MockClock;
use Tests\Implementation\PlayedGame;
use Tests\Implementation\Player;

/**
 * @covers \MightyBattle\GameQueue\Match\MatchService
 */
class MatchServiceTest extends TestCase
{
    private MatchService $service;
    private Cache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MatchService($this->cache = new Cache());
        Clock::setInstance($clock = new MockClock());
        $clock->setNow(new \DateTimeImmutable('2020-01-01T00:00:00+00:00'));
    }

    public function testEqualMatch(): void
    {
        $matches = $this->service->match(
            new TypedList(
                PlayerInterface::class,
                [
                    new Player('1', new Deck(10, 1000), 1, 1, 2, Clock::now()),
                    new Player('2', new Deck(28,  2800), 1, 0, 1, Clock::now()),
                    new Player('3', new Deck(10, 1000), 0, 0, 1, Clock::now()),
                    new Player('4', new Deck(28, 2800), 0, 0, 1, Clock::now()),
                ]
            )
        );

        $this->assertSame(2, $matches->size());

        /** @var QueueMatch $match */
        $match = $matches->first();
        /** @var QueueMatch $lastMatch */
        $lastMatch = $matches->last();

        $this->assertSame('1', $match->player()->id());
        $this->assertSame('3', $match->player2()->id());

        $this->assertSame('2', $lastMatch->player()->id());
        $this->assertSame('4', $lastMatch->player2()->id());
    }

    public function testBoundaryValues(): void
    {
        $this->service->recordResult(
            new PlayedGame(
                new Player('loser', new Deck(5, 500), 0, 0, 1, Clock::now()),
                new Player('winner', new Deck(8, 800), 0, 0, 3, Clock::now())
            )
        );
        $this->service->recordResult(
            new PlayedGame(
                new Player('loser', new Deck(10, 1200), 0, 0, 5, Clock::now()),
                new Player('winner', new Deck(15, 1800), 0, 0, 8, Clock::now())
            )
        );

        $b4 = new \DateTimeImmutable('2020-01-01T00:00:00+00:00');
        $now = new \DateTimeImmutable('2020-01-01T01:00:00+00:00');
        $matches = $this->service->match(
            new TypedList(
                PlayerInterface::class,
                [
                    new Player('1', new Deck(15, 1500), 1, 1, 2, $now),
                    new Player('2', new Deck(11, 1100), 0, 0, 1, $now),
                    new Player('3', new Deck(11, 1100), 0, 0, 1, $now),
                ]
            )
        );

        $this->assertSame(1, $matches->size());

        /** @var QueueMatch $match */
        $match = $matches->first();

        $this->assertSame('2', $match->player()->id());
        $this->assertSame('3', $match->player2()->id());
    }

    public function testPicksOppositeToLatelyLostMatch(): void
    {
        $this->service->recordResult(
            new PlayedGame(
                new Player('loser', new Deck(10, 1000), 0, 0, 1, Clock::now()),
                new Player('winner', new Deck(15, 1500), 0, 0, 2, Clock::now())
            )
        );

        $b4 = new \DateTimeImmutable('2020-01-01T00:00:00+00:00');
        $now = new \DateTimeImmutable('2020-01-01T01:00:00+00:00');
        $matches = $this->service->match(
            new TypedList(
                PlayerInterface::class,
                [
                    new Player('1', new Deck(15, 1500), 1, 1, 2, $now),
                    new Player('2', new Deck(10,  1000), 1, 0, 1, $b4),
                    new Player('3', new Deck(11, 1100), 0, 0, 1, $now),
                ]
            )
        );

        $this->assertSame(1, $matches->size());

        /** @var QueueMatch $match */
        $match = $matches->first();

        $this->assertSame('2', $match->player()->id());
        $this->assertSame('3', $match->player2()->id());
    }
}
