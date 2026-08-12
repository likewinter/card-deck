<?php

declare(strict_types=1);

namespace Likewinter\CardDeck\Card;

/**
 * A card suit. Pure identity — includes the Joker "suit" so jokers can
 * be represented as regular Cards.
 *
 * Suits have no intrinsic ordering; SuitOrder supplies trump and
 * lead-suit rules for trick-taking games.
 */
enum Suit: string
{
    case Joker = 'joker';
    case Hearts = 'hearts';
    case Diamonds = 'diamonds';
    case Clubs = 'clubs';
    case Spades = 'spades';

    /**
     * Look up a suit by its display symbol ("♥", "♦", "♣", "♠", "🃏").
     *
     * @throws \InvalidArgumentException If the symbol is not a known suit.
     */
    #[\NoDiscard]
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
        return array_values(array_filter(self::cases(), static fn(Suit $suit) => $suit !== self::Joker));
    }

    /**
     * The conventional color of the suit: red (hearts, diamonds) or
     * black (clubs, spades, joker). Used by games with alternating-color
     * rules, e.g. Solitaire tableau building.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Hearts, self::Diamonds => 'red',
            self::Clubs, self::Spades, self::Joker => 'black',
        };
    }

    /**
     * Returns the display symbol for the suit, e.g. "♠".
     */
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
