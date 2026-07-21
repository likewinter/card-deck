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

    public function isFace(): bool
    {
        return $this->rank->isFace();
    }
}
