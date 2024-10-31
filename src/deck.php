<?php

namespace Likewinter\CardDeck;

class Deck extends Stack
{
    public function __construct(
        /** @var list<Card> */
        protected array $cards = [],
        public readonly int $deckSize = 52,
    ) {
        // Validate deck size
        if ($deckSize <= 0) {
            throw new \InvalidArgumentException('Deck size must be positive');
        }

        parent::__construct($cards, $deckSize);
    }
}
