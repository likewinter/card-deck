<?php

namespace Likewinter\CardDeck;

class Deck extends Stack
{
    public function __construct(
        /** @var list<Card> */
        protected array $cards = [],
        public readonly int $deckSize = 52,
    ) {
        parent::__construct($cards, $deckSize);
    }

    public function shuffle(): void
    {
        shuffle($this->cards);
    }
}
