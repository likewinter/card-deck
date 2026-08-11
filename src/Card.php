<?php

declare(strict_types=1);

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\Card\Suit;

/**
 * A playing card: a rank paired with a suit. Immutable and identity-only.
 *
 * Card has no ordering and no game-specific semantics — those live in
 * RankOrder (for rank comparison) and SuitOrder (for trump/lead-suit
 * comparison). Use CardInPlay if you need face-up/face-down state.
 */
class Card implements PlayableCard
{
    /**
     * @param Suit $suit The card's suit.
     * @param Rank $rank The card's rank.
     *
     * @throws \InvalidArgumentException If exactly one of suit/rank is Joker — jokers pair the Joker suit with the Joker rank.
     */
    public function __construct(
        public readonly Suit $suit,
        public readonly Rank $rank,
    ) {
        if (
            $this->suit === Suit::Joker && $this->rank !== Rank::Joker
            || $this->rank === Rank::Joker && $this->suit !== Suit::Joker
        ) {
            throw new \InvalidArgumentException('Joker suit must have Joker rank');
        }
    }

    /**
     * Parse a card from its string form, e.g. "A♠", "10♦", or "🃏🃏" for
     * a joker.
     *
     * @throws \InvalidArgumentException If the string is not a valid card.
     */
    #[\NoDiscard]
    public static function fromString(string $string): self
    {
        if (mb_strlen($string) < 2 || mb_strlen($string) > 3) {
            throw new \InvalidArgumentException('Invalid card string');
        }
        $isLong = mb_strlen($string) === 3;
        $rank = $isLong ? mb_substr($string, 0, 2) : mb_substr($string, 0, 1);
        $suit = $isLong ? mb_substr($string, 2, 1) : mb_substr($string, 1, 1);

        return new self(suit: Suit::fromSymbol($suit), rank: Rank::fromSymbol($rank));
    }

    /**
     * Renders the card as rank symbol + suit symbol, e.g. "A♠".
     */
    public function __toString(): string
    {
        return "{$this->rank->getSymbol()}{$this->suit->getSymbol()}";
    }

    /**
     * Two Cards are equal when they have the same suit and rank.
     */
    public function equals(PlayableCard $other): bool
    {
        return $other instanceof self && $this->suit === $other->suit && $this->rank === $other->rank;
    }

    /**
     * Whether this card is a joker.
     */
    public function isJoker(): bool
    {
        return $this->rank === Rank::Joker;
    }

    /**
     * Returns this card itself — a Card is its own underlying card.
     */
    public function underlyingCard(): Card
    {
        return $this;
    }
}
