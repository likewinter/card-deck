<?php

namespace Likewinter\CardDeck\Games\Poker;

enum HandRank: int
{
    case HIGH_CARD = 0;
    case ONE_PAIR = 1;
    case TWO_PAIR = 2;
    case THREE_OF_A_KIND = 3;
    case STRAIGHT = 4;
    case FLUSH = 5;
    case FULL_HOUSE = 6;
    case FOUR_OF_A_KIND = 7;
    case STRAIGHT_FLUSH = 8;
    case ROYAL_FLUSH = 9;

    public function getName(): string
    {
        return match ($this) {
            self::ROYAL_FLUSH => 'Royal Flush',
            self::STRAIGHT_FLUSH => 'Straight Flush',
            self::FOUR_OF_A_KIND => 'Four of a Kind',
            self::FULL_HOUSE => 'Full House',
            self::FLUSH => 'Flush',
            self::STRAIGHT => 'Straight',
            self::THREE_OF_A_KIND => 'Three of a Kind',
            self::TWO_PAIR => 'Two Pair',
            self::ONE_PAIR => 'One Pair',
            default => 'High Card',
        };
    }
}
