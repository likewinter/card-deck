<?php

namespace Likewinter\CardDeck\Card;

enum Suit: string
{
    case Joker = 'joker';
    case Hearts = 'hearts';
    case Diamonds = 'diamonds';
    case Clubs = 'clubs';
    case Spades = 'spades';

    public static function fromSymbol(string $symbol): self
    {
        return match ($symbol) {
            '♥' => self::Hearts,
            '♦' => self::Diamonds,
            '♣' => self::Clubs,
            '♠' => self::Spades,
            '🃏' => self::Joker,
            default => throw new \InvalidArgumentException("Invalid suit symbol: {$symbol}"),
        };
    }

    /**
     * @return list<Suit>
     */
    public static function casesWithoutJoker(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn(Suit $suit) => $suit !== self::Joker
        ));
    }

    /**
     * @return list<Suit>
     */
    public static function standardSuits(): array
    {
        return self::casesWithoutJoker();
    }

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

    /**
     * Returns whether the suit is a standard playing card suit
     */
    public function isStandard(): bool
    {
        return $this !== self::Joker;
    }
}
