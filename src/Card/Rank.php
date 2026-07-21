<?php

namespace Likewinter\CardDeck\Card;

/**
 * A card rank. Pure identity — no intrinsic ordering or value.
 *
 * Different games order ranks differently (poker, blackjack, belote, skat,
 * pinochle all disagree). Use a Likewinter\CardDeck\RankOrder to compare
 * ranks or get their values within a specific game's rules.
 */
enum Rank: string
{
    case Joker = 'joker';
    case Two = '2';
    case Three = '3';
    case Four = '4';
    case Five = '5';
    case Six = '6';
    case Seven = '7';
    case Eight = '8';
    case Nine = '9';
    case Ten = '10';
    case Jack = 'J';
    case Queen = 'Q';
    case King = 'K';
    case Ace = 'A';

    public static function fromSymbol(string $symbol): self
    {
        return match ($symbol) {
            'A' => self::Ace,
            '2' => self::Two,
            '3' => self::Three,
            '4' => self::Four,
            '5' => self::Five,
            '6' => self::Six,
            '7' => self::Seven,
            '8' => self::Eight,
            '9' => self::Nine,
            '10' => self::Ten,
            'J' => self::Jack,
            'Q' => self::Queen,
            'K' => self::King,
            '🃏' => self::Joker,
            default => throw new \InvalidArgumentException("Invalid rank symbol: {$symbol}"),
        };
    }

    /**
     * @return list<Rank>
     */
    public static function casesWithoutJoker(): array
    {
        return array_values(array_filter(self::cases(), fn(Rank $rank) => $rank !== self::Joker));
    }

    /**
     * Returns the display symbol for the card rank.
     */
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
}
