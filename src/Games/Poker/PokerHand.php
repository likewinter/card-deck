<?php

namespace Likewinter\CardDeck\Games\Poker;

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Hand;

class PokerHand extends Hand
{
    public const HAND_SIZE = 5;
    public readonly HandRank $handRank;
    /** @var array<string, list<Card>> */
    public readonly array $rankSets;
    public readonly bool $isSameSuit;
    public readonly bool $isSequentialRank;

    public function __construct(
        /** @var list<Card> */
        public array $cards,
    ) {
        parent::__construct($cards, self::HAND_SIZE);
        $this->sortByRank();

        $this->rankSets = $this->getRankSets();
        $this->isSameSuit = $this->isSameSuit();
        $this->isSequentialRank = $this->isSequentialRank();
        $this->handRank = HandRank::getRankForHand($this);
    }

    /**
     * @return array<string,list<Card>>
     */
    protected function getRankSets(): array
    {
        $rankSets = [];
        foreach ($this->cards as $card) {
            $rankSets[$card->rank->getSymbol()][] = $card;
        }

        return $rankSets;
    }

    protected function isSameSuit(): bool
    {
        return count(array_unique(array_map(fn (Card $card) => $card->suit->getSymbol(), $this->cards))) === 1;
    }

    protected function isSequentialRank(): bool
    {
        $values = $this->getSortedUniqueRankValues();

        if (count($values) !== 5) {
            return false;
        }

        // Wheel: A-2-3-4-5 (Ace value 14 acts as 1)
        if ($values === [2, 3, 4, 5, 14]) {
            return true;
        }

        // Normal straight: 5 consecutive values
        for ($i = 1; $i < 5; $i++) {
            if ($values[$i] !== $values[$i - 1] + 1) {
                return false;
            }
        }

        return true;
    }

    public static function fromHand(Hand $hand): self
    {
        return new self(array_values([...$hand]));
    }

    /**
     * Returns the rank value of the highest card in the hand.
     * For the wheel (A-2-3-4-5), the effective high card is 5, not Ace.
     */
    public function getHighCardValue(): int
    {
        $values = $this->getSortedUniqueRankValues();

        // Wheel: Ace counts as 1, so 5 is the high card
        if ($values === [2, 3, 4, 5, 14]) {
            return 5;
        }

        return max($values);
    }

    /**
     * Returns the ordered list of rank values used to break ties between
     * hands of the same rank category. Higher-priority values come first.
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
            // FLUSH and HIGH_CARD: all 5 ranks, descending
            HandRank::FLUSH, HandRank::HIGH_CARD => $this->descendingRankValues(),
        };
    }

    /**
     * Sorted (ascending) unique rank values.
     *
     * @return list<int>
     */
    private function getSortedUniqueRankValues(): array
    {
        $values = array_map(fn (Card $card) => $card->rank->value, $this->cards);
        sort($values);

        return array_values(array_unique($values));
    }

    /**
     * All 5 rank values in descending order (highest first). Used for
     * FLUSH and HIGH_CARD comparisons where every card matters.
     *
     * @return list<int>
     */
    private function descendingRankValues(): array
    {
        $values = array_map(fn (Card $card) => $card->rank->value, $this->cards);
        rsort($values);

        return $values;
    }

    /**
     * Build a signature where the groups (pairs, trips, quads) come first
     * in priority order, followed by kickers in descending order.
     *
     * Example for ONE_PAIR of Ks with A-Q-J kickers: [13, 14, 12, 11]
     * Example for TWO_PAIR (As and Ks) with Q kicker: [14, 13, 12]
     * Example for FULL_HOUSE (As over Ks): [14, 13]
     *
     * @param int ...$groupSizes Priority order of group sizes to extract.
     * @return list<int>
     */
    private function groupedSignature(int ...$groupSizes): array
    {
        // Build [rankValue => count] map
        $counts = [];
        foreach ($this->cards as $card) {
            $value = $card->rank->value;
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        $signature = [];
        $remaining = $counts;

        // Extract each requested group size, picking the highest-ranked
        // group of that size each time.
        foreach ($groupSizes as $size) {
            $candidates = array_filter($remaining, fn (int $c) => $c === $size);
            if (empty($candidates)) {
                continue;
            }
            $keys = array_keys($candidates);
            rsort($keys);
            $signature[] = $keys[0];
            unset($remaining[$keys[0]]);
        }

        // Append remaining kickers in descending order
        $kickers = array_keys($remaining);
        rsort($kickers);
        foreach ($kickers as $k) {
            $signature[] = $k;
        }

        return $signature;
    }
}
