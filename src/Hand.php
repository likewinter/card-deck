<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\{Rank, Suit};

class Hand extends Stack
{
    public function __construct(
        /** @var list<Card> */
        protected array $cards = [],
        int $handSize = 5,
    ) {
        parent::__construct($cards, $handSize);
    }

    public function sortByRank(?RankOrder $rankOrder = null): void
    {
        $order = $rankOrder ?? RankOrder::poker();
        $this->sort(fn(Card $a, Card $b) => $order->compare($a->rank, $b->rank));
    }

    /** @return list<Rank> */
    public function getRanks(): array
    {
        return array_map(fn(Card $card) => $card->rank, $this->cards);
    }

    /** @return list<Suit> */
    public function getSuits(): array
    {
        return array_map(fn(Card $card) => $card->suit, $this->cards);
    }
}
