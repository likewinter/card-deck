<?php

declare(strict_types=1);

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
    /**
     * @param Card $wild The wild card itself (typically a joker).
     * @param Card|null $assigned The card it currently represents, if any.
     */
    public function __construct(
        public Card $wild,
        public ?Card $assigned = null,
    ) {}

    /**
     * Returns a new Wildcard with the assigned substitution card.
     */
    #[\NoDiscard]
    public function assign(Card $card): self
    {
        return new self($this->wild, $card);
    }

    /**
     * Returns a new Wildcard with no assignment (un-assigns the wildcard).
     */
    #[\NoDiscard]
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

    /**
     * Whether the wildcard currently represents a card.
     */
    public function isAssigned(): bool
    {
        return $this->assigned !== null;
    }

    /**
     * Whether the wildcard does not yet represent a card.
     */
    public function isUnassigned(): bool
    {
        return $this->assigned === null;
    }

    /**
     * Renders the assigned card if set, otherwise the wild card itself.
     */
    public function __toString(): string
    {
        if ($this->assigned !== null) {
            return (string) $this->assigned;
        }
        return (string) $this->wild;
    }

    /**
     * The assigned card if set, otherwise the wild card itself.
     */
    public function underlyingCard(): Card
    {
        return $this->assigned ?? $this->wild;
    }

    /**
     * Two wildcards are equal when their wild cards match, regardless of
     * assignment.
     */
    public function equals(PlayableCard $other): bool
    {
        return $other instanceof self && $this->wild->equals($other->wild);
    }
}
