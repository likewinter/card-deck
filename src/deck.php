<?php

namespace Likewinter\CardDeck;

class Deck
{
    use Stack;

    /** @var Card[] */
    public function __construct(
        private array $cards = [],
    ) {
    }

    public function shuffle(): void
    {
        shuffle($this->cards);
    }
}
