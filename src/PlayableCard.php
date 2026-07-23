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

    /**
     * Identity check: is this the same playable card as $other?
     * Used by Stack for removal and membership checks.
     *
     * Each implementation defines identity differently:
     * - Card: same suit and rank.
     * - CardInPlay: same underlying card AND same face state
     *   (a face-up A♠ is not equal to a face-down A♠).
     * - Wildcard: same wild card, regardless of assignment
     *   (an assigned joker equals an unassigned joker).
     *
     * Equality is type-narrowing: a Card never equals a CardInPlay
     * wrapping the same card, and vice versa.
     */
    public function equals(PlayableCard $other): bool;

    public function __toString(): string;
}
