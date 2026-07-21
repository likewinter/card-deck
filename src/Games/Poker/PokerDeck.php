<?php

namespace Likewinter\CardDeck\Games\Poker;

use Likewinter\CardDeck\{Deck, Card};
use Likewinter\CardDeck\Card\{Rank, Suit};

class PokerDeck extends Deck
{
    public const DECK_SIZE = 52;

    public function __construct()
    {
        parent::__construct(self::create(), self::DECK_SIZE);
    }

    /**
     * @return list<Card>
     */
    public static function create(): array
    {
        $cards = [];
        foreach (Rank::casesWithoutJoker() as $rank) {
            $cards = array_merge($cards, [
                new Card(suit: Suit::Clubs, rank: $rank),
                new Card(suit: Suit::Diamonds, rank: $rank),
                new Card(suit: Suit::Hearts, rank: $rank),
                new Card(suit: Suit::Spades, rank: $rank),
            ]);
        }

        return $cards;
    }
}
