<?php

namespace Likewinter\CardDeck\Games\Poker;

use Likewinter\CardDeck\Deck;
use Likewinter\CardDeck\DeckBuilder;

class PokerDeck extends Deck
{
    public const DECK_SIZE = 52;

    public function __construct()
    {
        parent::__construct(DeckBuilder::standard52()->buildCards(), self::DECK_SIZE);
    }
}
