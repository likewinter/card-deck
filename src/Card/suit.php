<?php

namespace Likewinter\CardDeck\Card;

enum Suit: string
{
    case Joker = 'joker';
    case Hearts = 'hearts';
    case Diamonds = 'diamonds';
    case Clubs = 'clubs';
    case Spades = 'spades';

    public function getColor(): string
    {
        return match ($this) {
            self::Hearts, self::Diamonds => 'red',
            self::Clubs, self::Spades => 'black',
            self::Joker => 'black',
        };
    }

    public function getSymbol(): string
    {
        return match ($this) {
            self::Hearts => '♥',
            self::Diamonds => '♦',
            self::Clubs => '♣',
            self::Spades => '♠',
            self::Joker => '🃏',
        };
    }
}
