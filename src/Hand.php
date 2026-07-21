<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\{Rank, Suit};

class Hand extends Stack
{
    public function __construct(
        /** @var list<Card> */
        protected array $cards = [],
        ?int $capacity = null,
    ) {
        parent::__construct($cards, $capacity);
    }

    public function sortByRank(RankOrder $rankOrder): void
    {
        $this->sort(fn(PlayableCard $a, PlayableCard $b) => $rankOrder->compare($a->underlyingCard()->rank, $b->underlyingCard()->rank));
    }

    /** @return list<Rank> */
    public function getRanks(): array
    {
        return array_map(fn(PlayableCard $card) => $card->underlyingCard()->rank, $this->cards);
    }

    /** @return list<Suit> */
    public function getSuits(): array
    {
        return array_map(fn(PlayableCard $card) => $card->underlyingCard()->suit, $this->cards);
    }
}
