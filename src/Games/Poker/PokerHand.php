<?php

namespace Likewinter\CardDeck\Games\Poker;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Hand;
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\Card\Rank;

/**
 * An immutable, classified 5-card poker hand.
 *
 * PokerHand is a value object: it holds exactly 5 Cards, computes the
 * hand rank eagerly, and exposes no mutation. It does NOT extend Hand
 * or Stack — it is a classified snapshot, not a mutable collection.
 *
 * @implements IteratorAggregate<int, Card>
 */
final readonly class PokerHand implements IteratorAggregate, \Countable, \Stringable
{
    public const HAND_SIZE = 5;

    public HandRank $handRank;
    /** @var array<string, list<Card>> */
    public array $rankSets;
    public bool $isSameSuit;
    public bool $isSequentialRank;
    public RankOrder $rankOrder;

    /** @var list<Card> */
    private array $cards;

    /**
     * @param list<Card> $cards Exactly 5 cards.
     */
    public function __construct(
        array $cards,
        ?RankOrder $rankOrder = null,
    ) {
        if (count($cards) !== self::HAND_SIZE) {
            throw new \InvalidArgumentException(
                sprintf('PokerHand requires exactly %d cards, got %d', self::HAND_SIZE, count($cards))
            );
        }

        $this->rankOrder = $rankOrder ?? RankOrder::poker();

        // Sort a copy by rank for classification
        $sorted = $cards;
        usort($sorted, fn(Card $a, Card $b) => $this->rankOrder->compare($a->rank, $b->rank));
        $this->cards = $sorted;

        $this->rankSets = $this->buildRankSets();
        $this->isSameSuit = $this->detectSameSuit();
        $this->isSequentialRank = $this->detectSequentialRank();
        $this->handRank = $this->classify();
    }

    /**
     * Build a PokerHand from a Hand's cards, resolving through
     * underlyingCard() so CardInPlay/Wildcard are unwrapped.
     */
    public static function fromHand(Hand $hand): self
    {
        $cards = array_map(
            fn($card) => $card->underlyingCard(),
            [...$hand],
        );

        return new self(array_values($cards));
    }

    /**
     * Compare this hand against another. Returns:
     *   -1 if this hand loses to $other
     *    0 if they tie
     *    1 if this hand beats $other
     */
    public function compare(self $other): int
    {
        if ($this->handRank !== $other->handRank) {
            return $this->handRank->value <=> $other->handRank->value;
        }

        $sa = $this->getTiebreakerSignature();
        $sb = $other->getTiebreakerSignature();

        for ($i = 0, $n = max(count($sa), count($sb)); $i < $n; $i++) {
            $va = $sa[$i] ?? 0;
            $vb = $sb[$i] ?? 0;
            if ($va !== $vb) {
                return $va <=> $vb;
            }
        }

        return 0;
    }

    /**
     * Returns the rank value of the highest card in the hand.
     * For the wheel (A-2-3-4-5), the effective high card is 5, not Ace.
     */
    public function getHighCardValue(): int
    {
        $values = $this->getSortedUniqueRankValues();

        if ($values === [2, 3, 4, 5, 14]) {
            return 5;
        }

        return max($values);
    }

    /**
     * Returns the ordered list of rank values used to break ties.
     *
     * @return list<int>
     */
    public function getTiebreakerSignature(): array
    {
        return match ($this->handRank) {
            HandRank::ROYAL_FLUSH => [],
            HandRank::STRAIGHT_FLUSH, HandRank::STRAIGHT => [$this->getHighCardValue()],
            HandRank::FOUR_OF_A_KIND => $this->groupedSignature(4),
            HandRank::FULL_HOUSE => $this->groupedSignature(3, 2),
            HandRank::THREE_OF_A_KIND => $this->groupedSignature(3),
            HandRank::TWO_PAIR => $this->groupedSignature(2, 2),
            HandRank::ONE_PAIR => $this->groupedSignature(2),
            HandRank::FLUSH, HandRank::HIGH_CARD => $this->descendingRankValues(),
        };
    }

    /** @return Iterator<int, Card> */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->cards);
    }

    public function count(): int
    {
        return count($this->cards);
    }

    public function __toString(): string
    {
        return implode(',', $this->cards);
    }

    // ── Classification ──────────────────────────────────────────────────

    private function classify(): HandRank
    {
        return match (true) {
            $this->isRoyalFlush() => HandRank::ROYAL_FLUSH,
            $this->isStraightFlush() => HandRank::STRAIGHT_FLUSH,
            $this->isFourOfAKind() => HandRank::FOUR_OF_A_KIND,
            $this->isFullHouse() => HandRank::FULL_HOUSE,
            $this->isFlush() => HandRank::FLUSH,
            $this->isStraight() => HandRank::STRAIGHT,
            $this->isThreeOfAKind() => HandRank::THREE_OF_A_KIND,
            $this->isTwoPair() => HandRank::TWO_PAIR,
            $this->isPair() => HandRank::ONE_PAIR,
            default => HandRank::HIGH_CARD,
        };
    }

    private function isRoyalFlush(): bool
    {
        return $this->isStraightFlush()
            && $this->rankOrder->isHighest(Rank::Ace)
            && $this->getHighCardValue() === $this->rankOrder->value(Rank::Ace);
    }

    private function isStraightFlush(): bool
    {
        return $this->isSequentialRank && $this->isSameSuit;
    }

    private function isFourOfAKind(): bool
    {
        $counts = $this->countRankGroups();

        return !empty($counts) && max($counts) === 4;
    }

    private function isFullHouse(): bool
    {
        return $this->countRankGroups() === [2, 3];
    }

    private function isFlush(): bool
    {
        return $this->isSameSuit;
    }

    private function isStraight(): bool
    {
        return $this->isSequentialRank;
    }

    private function isThreeOfAKind(): bool
    {
        $counts = $this->countRankGroups();

        return !empty($counts) && max($counts) === 3;
    }

    private function isTwoPair(): bool
    {
        return $this->countRankGroups() === [1, 2, 2];
    }

    private function isPair(): bool
    {
        $counts = $this->countRankGroups();

        return !empty($counts) && max($counts) === 2;
    }

    // ── Detection helpers ───────────────────────────────────────────────

    /**
     * @return array<string,list<Card>>
     */
    private function buildRankSets(): array
    {
        $rankSets = [];
        foreach ($this->cards as $card) {
            $rankSets[$card->rank->getSymbol()][] = $card;
        }

        return $rankSets;
    }

    private function detectSameSuit(): bool
    {
        return count(array_unique(array_map(fn(Card $card) => $card->suit->getSymbol(), $this->cards))) === 1;
    }

    private function detectSequentialRank(): bool
    {
        $values = $this->getSortedUniqueRankValues();

        if (count($values) !== 5) {
            return false;
        }

        if ($values === [2, 3, 4, 5, 14]) {
            return true;
        }

        for ($i = 1; $i < 5; $i++) {
            if ($values[$i] !== $values[$i - 1] + 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<int>
     */
    private function countRankGroups(): array
    {
        $counts = array_map(fn(array $cards) => count($cards), $this->rankSets);
        sort($counts);

        return $counts;
    }

    // ── Tiebreaker helpers ──────────────────────────────────────────────

    /**
     * @return list<int>
     */
    private function getSortedUniqueRankValues(): array
    {
        $values = array_map(fn(Card $card) => $this->rankOrder->value($card->rank), $this->cards);
        sort($values);

        return array_values(array_unique($values));
    }

    /**
     * @return list<int>
     */
    private function descendingRankValues(): array
    {
        $values = array_map(fn(Card $card) => $this->rankOrder->value($card->rank), $this->cards);
        rsort($values);

        return $values;
    }

    /**
     * @return list<int>
     */
    private function groupedSignature(int ...$groupSizes): array
    {
        $counts = [];
        foreach ($this->cards as $card) {
            $value = $this->rankOrder->value($card->rank);
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        $signature = [];
        $remaining = $counts;

        foreach ($groupSizes as $size) {
            $candidates = array_filter($remaining, fn(int $c) => $c === $size);
            if (empty($candidates)) {
                continue;
            }
            $keys = array_keys($candidates);
            rsort($keys);
            $signature[] = $keys[0];
            unset($remaining[$keys[0]]);
        }

        $kickers = array_keys($remaining);
        rsort($kickers);
        foreach ($kickers as $k) {
            $signature[] = $k;
        }

        return $signature;
    }
}
