<?php

namespace Likewinter\CardDeck;

/**
 * Trump configuration for a trick-taking game.
 *
 * - NoTrump: no trump for this deal (e.g. Hearts, Bridge NT contracts)
 * - Suit:    a specific suit is trump (e.g. Spades in the game Spades)
 */
enum Trump
{
    case NoTrump;
    case Suit;

    public function hasTrump(): bool
    {
        return $this === self::Suit;
    }
}
