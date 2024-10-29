<?php

namespace Likewinter\CardDeck\Card;

enum Rank: int
{
    case Joker = 0;
    case Two = 2;
    case Three = 3;
    case Four = 4;
    case Five = 5;
    case Six = 6;
    case Seven = 7;
    case Eight = 8;
    case Nine = 9;
    case Ten = 10;
    case Jack = 11;
    case Queen = 12;
    case King = 13;
    case Ace = 14;

    public function getSymbol(): string
    {
        return match ($this) {
            self::Ace => 'A',
            self::Two => '2',
            self::Three => '3',
            self::Four => '4',
            self::Five => '5',
            self::Six => '6',
            self::Seven => '7',
            self::Eight => '8',
            self::Nine => '9',
            self::Ten => '10',
            self::Jack => 'J',
            self::Queen => 'Q',
            self::King => 'K',
            self::Joker => '🃏',
        };
    }

    /**
     * @return list<Rank>
     */
    public static function casesWithoutJoker(): array
    {
        return array_filter(self::cases(), fn (Rank $rank) => $rank !== self::Joker);
    }
}
