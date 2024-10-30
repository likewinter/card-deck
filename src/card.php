<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\Card\Suit;

class Card
{
    public function __construct(
        public readonly Suit $suit,
        public readonly Rank $rank,
        public readonly mixed $meta = null,
    ) {
        if ($this->suit === Suit::Joker && $this->rank !== Rank::Joker ||
            $this->rank === Rank::Joker && $this->suit !== Suit::Joker) {
            throw new \InvalidArgumentException('Joker suit must have Joker rank');
        }
    }

    public function __toString(): string
    {
        return "{$this->rank->getSymbol()}{$this->suit->getSymbol()}";
    }
}
