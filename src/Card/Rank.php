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
        return array_filter(self::cases(), fn(Rank $rank) => $rank !== self::Joker);
    }

    /**
     * Returns the display symbol for the card rank
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

    /**
     * Determines if the rank is a face card (10 or higher)
     */
    public function isFaceCard(): bool
    {
        return $this->value >= self::Ten->value;
    }

    /**
     * Compares if this rank is higher than another rank
     */
    public function isHigherThan(self $other): bool
    {
        if ($this === self::Joker || $other === self::Joker) {
            throw new \InvalidArgumentException('Cannot compare Joker cards');
        }

        return $this->value > $other->value;
    }

    /**
     * Returns the next rank in sequence (null if at Ace)
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Ace => null,
            self::Joker => null,
            default => self::from($this->value + 1)
        };
    }

    /**
     * Returns the previous rank in sequence (null if at Two)
     */
    public function previous(): ?self
    {
        return match ($this) {
            self::Two => null,
            self::Joker => null,
            default => self::from($this->value - 1)
        };
    }
}
