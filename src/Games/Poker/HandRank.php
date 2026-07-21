<?php

namespace Likewinter\CardDeck\Games\Poker;

use Likewinter\CardDeck\Card\Rank;

enum HandRank: int
{
    case HIGH_CARD = 0;
    case ONE_PAIR = 1;
    case TWO_PAIR = 2;
    case THREE_OF_A_KIND = 3;
    case STRAIGHT = 4;
    case FLUSH = 5;
    case FULL_HOUSE = 6;
    case FOUR_OF_A_KIND = 7;
    case STRAIGHT_FLUSH = 8;
    case ROYAL_FLUSH = 9;

    public function getName(): string
    {
        return match ($this) {
            self::ROYAL_FLUSH => 'Royal Flush',
            self::STRAIGHT_FLUSH => 'Straight Flush',
            self::FOUR_OF_A_KIND => 'Four of a Kind',
            self::FULL_HOUSE => 'Full House',
            self::FLUSH => 'Flush',
            self::STRAIGHT => 'Straight',
            self::THREE_OF_A_KIND => 'Three of a Kind',
            self::TWO_PAIR => 'Two Pair',
            self::ONE_PAIR => 'One Pair',
            default => 'High Card',
        };
    }

    public static function getRankForHand(PokerHand $hand): self
    {
        return match (true) {
            self::isRoyalFlush($hand) => self::ROYAL_FLUSH,
            self::isStraightFlush($hand) => self::STRAIGHT_FLUSH,
            self::isFourOfAKind($hand) => self::FOUR_OF_A_KIND,
            self::isFullHouse($hand) => self::FULL_HOUSE,
            self::isFlush($hand) => self::FLUSH,
            self::isStraight($hand) => self::STRAIGHT,
            self::isThreeOfAKind($hand) => self::THREE_OF_A_KIND,
            self::isTwoPair($hand) => self::TWO_PAIR,
            self::isPair($hand) => self::ONE_PAIR,
            default => self::HIGH_CARD,
        };
    }

    /**
     * Compare two poker hands. Returns:
     *   -1 if $a loses to $b
     *    0 if they tie
     *    1 if $a beats $b
     *
     * Within the same hand rank, kickers and the relevant primary ranks
     * are compared in standard poker order.
     */
    public static function compare(PokerHand $a, PokerHand $b): int
    {
        if ($a->handRank !== $b->handRank) {
            return $a->handRank->value <=> $b->handRank->value;
        }

        $sa = $a->getTiebreakerSignature();
        $sb = $b->getTiebreakerSignature();

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
     * @return array<int>
     */
    protected static function countHandRanks(PokerHand $hand): array
    {
        $counts = array_map(fn(array $cards) => count($cards), $hand->rankSets);
        sort($counts);

        return $counts;
    }

    protected static function isFourOfAKind(PokerHand $hand): bool
    {
        $counts = self::countHandRanks($hand);

        return !empty($counts) && max($counts) === 4;
    }

    protected static function isThreeOfAKind(PokerHand $hand): bool
    {
        $counts = self::countHandRanks($hand);

        return !empty($counts) && max($counts) === 3;
    }

    protected static function isPair(PokerHand $hand): bool
    {
        $counts = self::countHandRanks($hand);

        return !empty($counts) && max($counts) === 2;
    }

    protected static function isFullHouse(PokerHand $hand): bool
    {
        return self::countHandRanks($hand) === [2, 3];
    }

    protected static function isTwoPair(PokerHand $hand): bool
    {
        return self::countHandRanks($hand) === [1, 2, 2];
    }

    protected static function isFlush(PokerHand $hand): bool
    {
        return $hand->isSameSuit;
    }

    protected static function isStraight(PokerHand $hand): bool
    {
        return $hand->isSequentialRank;
    }

    protected static function isStraightFlush(PokerHand $hand): bool
    {
        return $hand->isSequentialRank && $hand->isSameSuit;
    }

    protected static function isRoyalFlush(PokerHand $hand): bool
    {
        return self::isStraightFlush($hand) && $hand->getHighCardValue() === Rank::Ace->value;
    }
}
