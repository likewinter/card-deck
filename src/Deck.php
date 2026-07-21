<?php

namespace Likewinter\CardDeck;

class Deck extends Stack
{
    public function __construct(
        /** @var list<Card> */
        protected array $cards = [],
        ?int $capacity = 52,
    ) {
        parent::__construct($cards, $capacity);
    }
}
