<?php

namespace MightyBattle\GameQueue\Match;

use MightyBattle\GameQueue\Cache\CacheInterface;
use MightyBattle\GameQueue\Entity\PlayedGameInterface;
use MightyBattle\GameQueue\Entity\PlayerInterface;
use MightyBattle\GameQueue\Entity\WaitingPlayerInterface;
use MightyBattle\GameQueue\Util\ArrayUtil;
use MightyBattle\GameQueue\Util\NumberUtil;
use MightyBattle\GameQueue\Util\TypedList;
use Psr\Log\LoggerInterface;

class MatchService
{
    private CacheInterface $cache;
    private LoggerInterface $logger;
    /**
     * @var History[]
     */
    private array $cardsCountHistory;
    /**
     * @var History[]
     */
    private array $deckPointsHistory;
    /**
     * @var History[]
     */
    private array $levelHistory;

    private string $deckPointsCacheKey = 'deck_points_history';
    private string $cardsCountCacheKey = 'cards_count_history';
    private string $levelCacheKey = 'level_history';

    public function __construct(CacheInterface $cache, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * @param TypedList<WaitingPlayerInterface> $playersArg
     * @return TypedList<QueueMatch>
     */
    public function match(TypedList $playersArg): TypedList
    {
        $players = $playersArg->duplicate();
        $this->init();
        $list = new TypedList(QueueMatch::class);

        $prevSize = $players->size();
        do {
            $this->lookUpMatches($players, $list);
            if ($players->size() === $prevSize) {
                break;
            }
            $prevSize = $players->size();
        } while(!$players->isEmpty() && $players->size() > 1);

        return $list;
    }

    public function recordResult(PlayedGameInterface $playedGame): void
    {
        $this->init();

        $this->recordCardCount($playedGame);
        $this->recordDeckPoints($playedGame);
        $this->recordLevel($playedGame);
    }

    private function init(): void
    {
        $this->levelHistory = $this->mapHistory($this->cache->getOrDefault($this->levelCacheKey));
        $this->cardsCountHistory = $this->mapHistory($this->cache->getOrDefault($this->cardsCountCacheKey));
        $this->deckPointsHistory = $this->mapHistory($this->cache->getOrDefault($this->deckPointsCacheKey));
    }

    /**
     * @return History[]
     */
    private function mapHistory(array $data): array
    {
        $histories = [];

        foreach ($data as $historyData) {
            // TODO: History::fromArray
            $histories[] = new History($historyData['low'], $historyData['high'], $historyData['won'], $historyData['lost']);
        }

        return $histories;
    }

    /**
     * @param History[] $histories
     */
    private function historiesToArray(array $histories): array
    {
        $data = [];

        foreach ($histories as $history) {
            $data[] = $history->toArray();
        }

        return $data;
    }

    /**
     * @param TypedList<WaitingPlayerInterface> $players
     * @param TypedList<QueueMatch> $matches
     */
    private function lookUpMatches(TypedList $players, TypedList $matches): void
    {
        if ($players->size() < 2) {
           return;
        }

        $playersByWaitingTimeDesc = $players->sort(
            function (WaitingPlayerInterface $a, WaitingPlayerInterface $b): int {
                if ($a->waitingSince()->getTimestamp() > $b->waitingSince()->getTimestamp()) {
                    return 1;
                } else if($a->waitingSince()->getTimestamp() < $b->waitingSince()->getTimestamp()) {
                    return -1;
                }

                return 0;
            }
        );

        /** @var WaitingPlayerInterface $first */
        $first = $playersByWaitingTimeDesc->first();
        $matchingPlayer = $this->findMatchingOne($first, $players->filter(fn(WaitingPlayerInterface $player): bool => $player !== $first));

        $players->filter(fn($p) => $p !== $first, true);
        if ($matchingPlayer != null) {
            $this->logger->debug('Chance for ' . $first->id() . ' with ' . $matchingPlayer->id());
            $players->filter(fn($p) => $p !== $matchingPlayer, true);
            $matches->add(new QueueMatch($first, $matchingPlayer));
        } else {
            $this->logger->debug('No Chances For ' . $first->id());
        }
    }

    /**
     * @param TypedList<WaitingPlayerInterface> $waitingPlayers
     */
    private function findMatchingOne(WaitingPlayerInterface $playerToMatch, TypedList $waitingPlayers): ?WaitingPlayerInterface
    {
        $chances = new TypedList(MatchChance::class);
        /** @var WaitingPlayerInterface $waitingPlayer */
        foreach ($waitingPlayers->values() as $waitingPlayer) {
            $chance = $this->calculateChance($playerToMatch, $waitingPlayer);
            $this->logger->debug('calculated chance for ' . $playerToMatch->id() . ' against ' . $waitingPlayer->id() . ' is ' . ((int) ($chance->chance() * 100)) . '%');
            $chances->add($chance);
        }

        $closestChance = $this->findClosestChance($chances);

        if ($closestChance === null) {
            return null;
        }

        return $closestChance->getWaitingPlayer();
    }

    private function recordCardCount(PlayedGameInterface $playedGame): void
    {
        /** @var PlayerInterface $lowCardsCountPlayer */
        $lowCardsCountPlayer = $playedGame->players()->get(0);
        /** @var PlayerInterface $highCardsCountPlayer */
        $highCardsCountPlayer = $playedGame->players()->get(1);
        if ($lowCardsCountPlayer->deck()->cardsCount() > $highCardsCountPlayer->deck()->cardsCount()) {
            $tmp = $lowCardsCountPlayer;
            $lowCardsCountPlayer = $highCardsCountPlayer;
            $highCardsCountPlayer = $tmp;
        }

        $lowCardsCount = $lowCardsCountPlayer->deck()->cardsCount();
        $highCardsCount = $highCardsCountPlayer->deck()->cardsCount();

        $history = null;

        $found = false;
        foreach ($this->deckPointsHistory as $pastHistory) {
            if ($pastHistory->getLowValue() === $lowCardsCount && $pastHistory->getHighValue() === $highCardsCount) {
                $history = $pastHistory;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $history = new History(
                $lowCardsCount,
                $highCardsCount,
                0,
                0
            );

            $this->cardsCountHistory[] = $history;
        }

        if ($playedGame->winner()->id() === $lowCardsCountPlayer->id()) {
            $history->increaseWon();
        } else {
            $history->increaseLost();
        }

        $this->cache->set($this->cardsCountCacheKey, $this->historiesToArray($this->cardsCountHistory));
    }


    private function recordDeckPoints(PlayedGameInterface $playedGame): void
    {
        /** @var PlayerInterface $lowDeckPlayer */
        $lowDeckPlayer = $playedGame->players()->get(0);
        /** @var PlayerInterface $highDeckPlayer */
        $highDeckPlayer = $playedGame->players()->get(1);
        if ($lowDeckPlayer->deck()->cardsPoints() > $highDeckPlayer->deck()->cardsPoints()) {
            $tmp = $lowDeckPlayer;
            $lowDeckPlayer = $highDeckPlayer;
            $highDeckPlayer = $tmp;
        }

        $lowDeckPoints = $lowDeckPlayer->deck()->cardsPoints();
        $highDeckPoints = $highDeckPlayer->deck()->cardsPoints();

        $history = null;

        $found = false;
        foreach ($this->deckPointsHistory as $pastHistory) {
            if ($pastHistory->getLowValue() === $lowDeckPoints && $pastHistory->getHighValue() === $highDeckPoints) {
                $history = $pastHistory;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $history = new History(
                $lowDeckPoints,
                $highDeckPoints,
                0,
                0
            );

            $this->deckPointsHistory[] = $history;
        }

        if ($playedGame->winner()->id() === $lowDeckPlayer->id()) {
            $history->increaseWon();
        } else {
            $history->increaseLost();
        }

        $this->cache->set($this->deckPointsCacheKey, $this->historiesToArray($this->deckPointsHistory));
    }

    private function recordLevel(PlayedGameInterface $playedGame): void
    {
        $this->init();

        /** @var PlayerInterface $lowLevelPlayer */
        $lowLevelPlayer = $playedGame->players()->get(0);
        /** @var PlayerInterface $highLevelPlayer */
        $highLevelPlayer = $playedGame->players()->get(1);
        if ($lowLevelPlayer->level() > $highLevelPlayer->level()) {
            $tmp = $lowLevelPlayer;
            $lowLevelPlayer = $highLevelPlayer;
            $highLevelPlayer = $tmp;
        }

        $lowLevel = $lowLevelPlayer->level();
        $highLevel = $highLevelPlayer->level();

        $history = null;

        $found = false;
        foreach ($this->levelHistory as $pastHistory) {
            if ($pastHistory->getLowValue() === $lowLevel && $pastHistory->getHighValue() === $highLevel) {
                $history = $pastHistory;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $history = new History(
                $lowLevel,
                $highLevel,
                0,
                0
            );

            $this->levelHistory[] = $history;
        }

        if ($playedGame->winner()->id() === $lowLevelPlayer->id()) {
            $history->increaseWon();
        } else {
            $history->increaseLost();
        }

        $this->cache->set($this->levelCacheKey, $this->historiesToArray($this->levelHistory));
    }

    private function calculateChance(WaitingPlayerInterface $playerToMatch, WaitingPlayerInterface $waitingPlayer): MatchChance
    {
        $levelChance = $this->calculateLevelChance($playerToMatch, $waitingPlayer);
        $cardsChance = $this->calculateCardsCountChance($playerToMatch, $waitingPlayer);
        $pointsChance = $this->calculateDeckPointsChance($playerToMatch, $waitingPlayer);

        return new MatchChance($waitingPlayer, $levelChance, $cardsChance, $pointsChance);
    }

    private function calculateLevelChance(WaitingPlayerInterface $playerToMatch, WaitingPlayerInterface $waitingPlayer): float
    {
        /** @var int $lowLevel */
        $lowLevel = min($waitingPlayer->level(), $playerToMatch->level());
        /** @var int $highLevel */
        $highLevel = max($waitingPlayer->level(), $playerToMatch->level());
        /** @var History $levelItem */
        $levelItem = ArrayUtil::findOrDefault($this->levelHistory, fn(History $historyItem): bool => $historyItem->getLowValue() === $lowLevel && $historyItem->getHighValue() === $highLevel, $lowLevel === $highLevel ? new History($lowLevel, $highLevel, 1, 1) : null);

        if ($levelItem !== null) {
            $sum = ($levelItem->getWon() + $levelItem->getLost());

            // if there was only one such game, cant say anything about chances
            if ($sum < 10) {
                return 0.5;
            }

            if ($waitingPlayer->level() === $lowLevel) {
                return $levelItem->getLost() / $sum;
            }

            return $levelItem->getWon() / $sum;
        }

        $lowLowBoundary = $lowLevel * 0.7;
        $highLowBoundary = $lowLevel * 1.3;

        $lowHighBoundary = $highLevel * 0.7;
        $highHighBoundary = $highLevel * 1.3;

        $closeItems = array_filter(
            $this->levelHistory,
            function (History $historyItem) use ($lowLowBoundary, $highLowBoundary, $lowHighBoundary, $highHighBoundary): bool {
                return $historyItem->getLowValue() >= $lowLowBoundary && $historyItem->getLowValue() <= $highLowBoundary && $historyItem->getHighValue() >= $lowHighBoundary && $historyItem->getHighValue() <= $highHighBoundary;
            }
        );

        $diffLowComp = 0;
        $diffHighComp = 0;

        $first = new History($lowLevel, $highLevel, 1, 1);
        $last = new History($lowLevel, $highLevel, 1, 1);

        /** @var History $closeItem */
        foreach ($closeItems as $closeItem) {
            $diffLow = abs($closeItem->getLowValue() - $lowLevel);
            if ($closeItem->getLowValue() <= $lowLevel && $diffLow <= $diffLowComp) {
                $first = $closeItem;
                break;
            }

            $diffHigh = abs($lowLevel - $closeItem->getLowValue());
            if ($closeItem->getLowValue() >= $lowLevel && $diffHigh <= $diffHighComp) {
                $last = $closeItem;
            }
        }

        $firstChance = $first->getWon() / ($first->getWon() + $first->getLost());
        $lastChance = $last->getWon() / ($last->getWon() + $last->getLost());

        return ($firstChance + $lastChance) / 2;
    }

    private function calculateCardsCountChance(WaitingPlayerInterface $playerToMatch, WaitingPlayerInterface $waitingPlayer): float
    {
        /** @var int $lowCards */
        $lowCards = min($waitingPlayer->deck()->cardsCount(), $playerToMatch->deck()->cardsCount());
        /** @var int $highCards */
        $highCards = max($waitingPlayer->deck()->cardsCount(), $playerToMatch->deck()->cardsCount());

        /** @var History[] $cardsCountItems */
        $cardsCountItems = array_filter($this->cardsCountHistory,
            fn(History $historyItem): bool => NumberUtil::checkDiff($lowCards, $historyItem->getLowValue(), 5) && NumberUtil::checkDiff($highCards, $historyItem->getHighValue(), 5)
        );

        if (count($cardsCountItems) < 10) {
            return 0.5;
        }

        $diffLowComp = 0;
        $diffHighComp = 0;

        $first = new History($lowCards, $highCards, 1, 1);
        $last = new History($lowCards, $highCards, 1, 1);

        /** @var History $closeItem */
        foreach ($cardsCountItems as $closeItem) {
            $diffLow = abs($closeItem->getLowValue() - $lowCards);
            if ($closeItem->getLowValue() <= $lowCards && $diffLow <= $diffLowComp) {
                $diffLowComp = $diffLow;
                $first = $closeItem;
                break;
            }

            $diffHigh = abs($lowCards - $closeItem->getLowValue());
            if ($closeItem->getLowValue() >= $lowCards && $diffHigh <= $diffHighComp) {
                $diffHighComp = $diffHigh;
                $last = $closeItem;
            }
        }

        $firstChance = $first->getWon() / ($first->getWon() + $first->getLost());
        $lastChance = $last->getWon() / ($last->getWon() + $last->getLost());

        return ($firstChance + $lastChance) / 2;
    }

    private function calculateDeckPointsChance(WaitingPlayerInterface $playerToMatch, WaitingPlayerInterface $waitingPlayer): float
    {
        /** @var int $lowPoints */
        $lowPoints = min($waitingPlayer->deck()->cardsPoints(), $playerToMatch->deck()->cardsPoints());
        /** @var int $highPoints */
        $highPoints = max($waitingPlayer->deck()->cardsPoints(), $playerToMatch->deck()->cardsPoints());

        /** @var History[] $deckPointsItems */
        $deckPointsItems = array_filter($this->deckPointsHistory,
            fn(History $historyItem): bool => NumberUtil::checkDiff($lowPoints, $historyItem->getLowValue(), 150) && NumberUtil::checkDiff($highPoints, $historyItem->getHighValue(), 150)
        );

        if (count($deckPointsItems) < 10) {
            return 0.5;
        }

        $diffLowComp = 0;
        $diffHighComp = 0;

        $first = new History($lowPoints, $highPoints, 1, 1);
        $last = new History($lowPoints, $highPoints, 1, 1);

        /** @var History $closeItem */
        foreach ($deckPointsItems as $closeItem) {
            $diffLow = abs($closeItem->getLowValue() - $lowPoints);
            if ($closeItem->getLowValue() <= $lowPoints && $diffLow <= $diffLowComp) {
                $first = $closeItem;
                break;
            }

            $diffHigh = abs($lowPoints - $closeItem->getLowValue());
            if ($closeItem->getLowValue() >= $lowPoints && $diffHigh <= $diffHighComp) {
                $last = $closeItem;
            }
        }

        $firstChance = $first->getWon() / ($first->getWon() + $first->getLost());
        $lastChance = $last->getWon() / ($last->getWon() + $last->getLost());

        return ($firstChance + $lastChance) / 2;
    }

    /**
     * @param TypedList<MatchChance> $chances
     */
    private function findClosestChance(TypedList $chances): ?MatchChance
    {
        /** @var MatchChance $chanceItem */
        $chanceItem = $chances->first();
        $chance = $chanceItem->chance();

        /** @var MatchChance $chanceComp */
        foreach ($chances->slice(1)->values() as $chanceComp) {
            $compChance = $chanceComp->chance();

            if ($compChance === 0.5) {
                $chanceItem = $chanceComp;
                break;
            }

            $distanceCompChance = abs(0.5 - $compChance);
            $distancePrevChance = abs(0.5 - $chance);

            if ($distanceCompChance < $distancePrevChance) {
                $chanceItem = $chanceComp;
                $chance = $compChance;
            }
        }

        if ($chanceItem->chance() < 0.4 || $chanceItem->chance() > 0.6) {
            return null;
        }

        return $chanceItem;
    }
}
