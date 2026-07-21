<?php

namespace Likewinter\CardDeck;

/**
 * Common interface for anything that can occupy a slot in a Stack.
 *
 * Card is the base case. CardInPlay wraps a Card with face-up/face-down
 * state. Wildcard wraps a Card with an optional substitution. All three
 * can live in a Stack, and all three can be compared by their string
 * representation for equality checks.
 */
interface PlayableCard
{
    /**
     * The underlying Card this playable card represents.
     * For Card itself, this returns $this.
     */
    public function underlyingCard(): Card;

    public function __toString(): string;
}
