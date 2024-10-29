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
            $cards[] = new Card(Suit::Clubs, $rank);
            $cards[] = new Card(Suit::Diamonds, $rank);
            $cards[] = new Card(Suit::Hearts, $rank);
            $cards[] = new Card(Suit::Spades, $rank);
        }

        return $cards;
    }
}
