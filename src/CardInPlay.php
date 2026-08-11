<?php

declare(strict_types=1);

namespace Likewinter\CardDeck;

/**
 * A card with a face-up or face-down orientation, for games with partial
 * information (Solitaire, War, any game where some cards are hidden).
 *
 * Card itself is immutable and orientation-agnostic. CardInPlay wraps a
 * Card and carries a Face state. Flipping returns a new instance rather
 * than mutating, preserving immutability of the underlying card.
 *
 * Games that don't need face-down state can use Card directly and ignore
 * this class entirely.
 */
final readonly class CardInPlay implements PlayableCard
{
    /**
     * @param Card $card The wrapped card.
     * @param Face $face Orientation; defaults to face-up.
     */
    public function __construct(
        public Card $card,
        public Face $face = Face::Up,
    ) {}

    /**
     * Create a face-up card in play.
     */
    public static function up(Card $card): self
    {
        return new self($card, Face::Up);
    }

    /**
     * Create a face-down card in play.
     */
    public static function down(Card $card): self
    {
        return new self($card, Face::Down);
    }

    /**
     * Returns a new instance with the opposite face. Does not mutate.
     */
    #[\NoDiscard]
    public function flip(): self
    {
        return new self($this->card, $this->face === Face::Up ? Face::Down : Face::Up);
    }

    /**
     * Returns a new instance face-up. No-op if already up.
     */
    #[\NoDiscard]
    public function reveal(): self
    {
        return $this->face === Face::Up ? $this : new self($this->card, Face::Up);
    }

    /**
     * Returns a new instance face-down. No-op if already down.
     */
    #[\NoDiscard]
    public function hide(): self
    {
        return $this->face === Face::Down ? $this : new self($this->card, Face::Down);
    }

    /**
     * Whether the card is currently face-up.
     */
    public function isFaceUp(): bool
    {
        return $this->face->isUp();
    }

    /**
     * Whether the card is currently face-down.
     */
    public function isFaceDown(): bool
    {
        return $this->face->isDown();
    }

    /**
     * Renders "██" when face-down, otherwise the wrapped card's string form.
     */
    public function __toString(): string
    {
        return $this->face === Face::Down ? '██' : (string) $this->card;
    }

    /**
     * Returns the wrapped card, regardless of face state.
     */
    public function underlyingCard(): Card
    {
        return $this->card;
    }

    /**
     * Equal when both the wrapped card and the face state match.
     */
    public function equals(PlayableCard $other): bool
    {
        return $other instanceof self && $this->card->equals($other->card) && $this->face === $other->face;
    }
}
