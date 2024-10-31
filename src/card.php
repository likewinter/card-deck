<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\Card\Suit;

class Card
{
    public function __construct(
        public readonly Suit $suit,
        public readonly Rank $rank,
    ) {
        if (
            $this->suit === Suit::Joker && $this->rank !== Rank::Joker ||
            $this->rank === Rank::Joker && $this->suit !== Suit::Joker
        ) {
            throw new \InvalidArgumentException('Joker suit must have Joker rank');
        }
    }

    public function __toString(): string
    {
        return "{$this->rank->getSymbol()}{$this->suit->getSymbol()}";
    }

    public function equals(self $other): bool
    {
        return $this->suit === $other->suit && $this->rank === $other->rank;
    }

    public function isHigherThan(self $other): bool
    {
        return $this->rank->isHigherThan($other->rank);
    }

    public function isSameSuitAs(self $other): bool
    {
        return $this->suit === $other->suit;
    }

    public function isSameRankAs(self $other): bool
    {
        return $this->rank === $other->rank;
    }

    public function isJoker(): bool
    {
        return $this->rank === Rank::Joker;
    }

    public function isAce(): bool
    {
        return $this->rank === Rank::Ace;
    }

    public function isFaceCard(): bool
    {
        return $this->rank->isFaceCard();
    }
}
