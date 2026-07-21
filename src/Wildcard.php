<?php

namespace Likewinter\CardDeck;

/**
 * A wildcard (typically a joker) that can stand in for another card.
 *
 * Card itself is immutable and cannot be reassigned to a different
 * rank/suit. Wildcard wraps a joker (or any card acting as wild) and
 * tracks which card it currently represents. Assignment returns a new
 * Wildcard instance, preserving immutability.
 *
 * Games without wildcards (standard poker, bridge, etc.) can ignore this
 * class entirely. Games with wildcards (Canasta, joker poker, Crazy
 * Eights with wild 8s, Rummy variants) use Wildcard to track the
 * substitution without mutating the underlying Card.
 */
final readonly class Wildcard implements PlayableCard
{
    public function __construct(
        public Card $wild,
        public ?Card $assigned = null,
    ) {}

    /**
     * Returns a new Wildcard with the assigned substitution card.
     */
    public function assign(Card $card): self
    {
        return new self($this->wild, $card);
    }

    /**
     * Returns a new Wildcard with no assignment (un-assigns the wildcard).
     */
    public function unassign(): self
    {
        return new self($this->wild, null);
    }

    /**
     * The card this wildcard currently represents, or null if unassigned.
     */
    public function effective(): ?Card
    {
        return $this->assigned;
    }

    public function isAssigned(): bool
    {
        return $this->assigned !== null;
    }

    public function isUnassigned(): bool
    {
        return $this->assigned === null;
    }

    public function __toString(): string
    {
        if ($this->assigned !== null) {
            return (string) $this->assigned;
        }
        return (string) $this->wild;
    }

    public function underlyingCard(): Card
    {
        return $this->assigned ?? $this->wild;
    }
}
