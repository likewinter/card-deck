<?php

namespace Likewinter\CardDeck\Games\Blackjack;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\Stack;
use Likewinter\CardDeck\Card\Rank;

/**
 * An immutable blackjack hand with additive value calculation.
 *
 * Unlike PokerHand (classification), blackjack scoring is a sum:
 * each card contributes its RankOrder value, with Aces counting as
 * 11 until that would bust, then as 1.
 *
 * @implements IteratorAggregate<int, Card>
 */
final readonly class BlackjackHand implements IteratorAggregate, \Countable, \Stringable
{
    private RankOrder $rankOrder;

    /** @var list<Card> */
    private array $cards;

    /**
     * @param list<Card> $cards
     */
    public function __construct(array $cards, ?RankOrder $rankOrder = null)
    {
        $this->cards = array_values($cards);
        $this->rankOrder = $rankOrder ?? RankOrder::blackjack();
    }

    public static function fromHand(Stack $hand): self
    {
        $cards = array_map(
            fn($card) => $card->underlyingCard(),
            [...$hand],
        );

        return new self(array_values($cards));
    }

    /**
     * The best hand value: highest total ≤ 21, or the lowest total
     * if every combination busts. Aces count as 11 until they bust,
     * then as 1.
     */
    public function value(): int
    {
        $total = 0;
        $aces = 0;

        foreach ($this->cards as $card) {
            $total += $this->rankOrder->value($card->rank);
            if ($card->rank === Rank::Ace) {
                $aces++;
            }
        }

        while ($total > 21 && $aces > 0) {
            $total -= 10;
            $aces--;
        }

        return $total;
    }

    public function isBust(): bool
    {
        return $this->value() > 21;
    }

    /**
     * A natural blackjack: exactly 2 cards totaling 21.
     */
    public function isBlackjack(): bool
    {
        return count($this->cards) === 2 && $this->value() === 21;
    }

    /**
     * Whether the hand contains an Ace counted as 11 (soft hand).
     */
    public function isSoft(): bool
    {
        $total = 0;
        $aces = 0;

        foreach ($this->cards as $card) {
            $total += $this->rankOrder->value($card->rank);
            if ($card->rank === Rank::Ace) {
                $aces++;
            }
        }

        // If reducing aces would change the value, the hand is soft
        return $aces > 0 && $total <= 21;
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
}
