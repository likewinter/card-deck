<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\Card\Suit;

/**
 * A playing card: a rank paired with a suit. Immutable and identity-only.
 *
 * Card has no ordering and no game-specific semantics — those live in
 * RankOrder (for rank comparison) and SuitOrder (for trump/lead-suit
 * comparison). Use CardInPlay if you need face-up/face-down state.
 */
class Card implements PlayableCard
{
    public function __construct(
        public readonly Suit $suit,
        public readonly Rank $rank,
    ) {
        if (
            ($this->suit === Suit::Joker && $this->rank !== Rank::Joker) ||
            ($this->rank === Rank::Joker && $this->suit !== Suit::Joker)
        ) {
            throw new \InvalidArgumentException('Joker suit must have Joker rank');
        }
    }

    public static function fromString(string $string): self
    {
        if (mb_strlen($string) < 2 || mb_strlen($string) > 3) {
            throw new \InvalidArgumentException('Invalid card string');
        }
        if (mb_strlen($string) === 3) {
            $rank = mb_substr($string, 0, 2);
            $suit = mb_substr($string, 2, 1);
        } else {
            $rank = mb_substr($string, 0, 1);
            $suit = mb_substr($string, 1, 1);
        }

        return new self(suit: Suit::fromSymbol($suit), rank: Rank::fromSymbol($rank));
    }

    public function __toString(): string
    {
        return "{$this->rank->getSymbol()}{$this->suit->getSymbol()}";
    }

    public function equals(PlayableCard $other): bool
    {
        return $other instanceof self
            && $this->suit === $other->suit
            && $this->rank === $other->rank;
    }

    public function isJoker(): bool
    {
        return $this->rank === Rank::Joker;
    }

    public function underlyingCard(): Card
    {
        return $this;
    }
}
