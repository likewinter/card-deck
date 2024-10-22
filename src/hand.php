<?php

namespace Likewinter\CardDeck;

class Hand
{
    use Stack;

    /** @var Card[] */
    public function __construct(
        private array $cards = [],
    ) {
    }

    public function sort(): void
    {
        usort($this->cards, fn (Card $a, Card $b) => $a->rank->value - $b->rank->value);
    }

    public function getRanks(): array
    {
        return array_map(fn ($card) => $card->rank, $this->getCards());
    }

    public function getSuits(): array
    {
        return array_map(fn ($card) => $card->suit, $this->getCards());
    }
}
