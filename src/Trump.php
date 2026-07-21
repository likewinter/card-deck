<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\Suit;

/**
 * Trump configuration for a trick-taking game.
 *
 * - None:   no trump (lowest-precedence game, e.g. Hearts)
 * - Suit:   a specific suit is trump (e.g. Spades in the game Spades)
 * - NoTrump: explicitly no trump for this hand (e.g. Bridge NT contracts)
 *
 * The distinction between None and NoTrump is semantic: None means the
 * game never has trump, NoTrump means this particular hand/deal has no
 * trump but others might. SuitOrder treats them identically for comparison.
 */
enum Trump
{
    case None;
    case Suit;
    case NoTrump;

    public function isSuit(): bool
    {
        return $this === self::Suit;
    }

    public function hasTrump(): bool
    {
        return $this === self::Suit;
    }
}
