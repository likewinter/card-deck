<?php

declare(strict_types=1);

namespace Likewinter\CardDeck;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\Card\Suit;

/**
 * An ordered collection of playable cards with an optional capacity.
 *
 * Orientation: the top of the stack is the FRONT of the internal list —
 * takeTop()/peek() operate there — while addCards() appends to the
 * bottom. peek* methods inspect without removing; take* methods remove
 * and return the taken cards as a NEW Stack; move* methods transfer
 * cards between stacks.
 *
 * Moves are transactional: if the target rejects the cards (for example
 * because its capacity would be exceeded), the cards are returned to
 * the source stack and the exception propagates.
 *
 * @implements IteratorAggregate<int, PlayableCard>
 */
class Stack implements IteratorAggregate, \Countable
{
    /** @var list<PlayableCard> */
    protected array $cards = [];

    /**
     * @param iterable<mixed, mixed> $cards Any iterable of items; each must implement PlayableCard (validated at runtime).
     * @param int|null $capacity Maximum number of cards this stack may hold; null means unlimited.
     *
     * @throws \InvalidArgumentException If capacity < 1, an item is not a PlayableCard, or the initial cards exceed capacity.
     */
    public function __construct(
        iterable $cards = [],
        public readonly ?int $capacity = null,
    ) {
        if ($capacity !== null && $capacity < 1) {
            throw new \InvalidArgumentException('Stack capacity must be greater than 0');
        }

        $list = [];
        foreach ($cards as $card) {
            if (!$card instanceof PlayableCard) {
                throw new \InvalidArgumentException('All cards must implement PlayableCard');
            }
            $list[] = $card;
        }

        if ($capacity !== null && count($list) > $capacity) {
            throw new \InvalidArgumentException('Stack capacity exceeded');
        }

        $this->cards = $list;
    }

    /**
     * Parse a comma-separated card string (e.g. "A♠,K♥,10♦") into a stack.
     *
     * @throws \InvalidArgumentException If any segment is not a valid card string.
     */
    #[\NoDiscard]
    public static function fromString(string $string, ?int $capacity = null): self
    {
        if ($string === '') {
            return new self();
        }

        $cards = explode(',', $string);

        return new self(array_map(Card::fromString(...), $cards), $capacity);
    }

    /**
     * Iterates over the cards from top to bottom.
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->cards);
    }

    /**
     * Returns the number of cards currently in the stack.
     */
    public function count(): int
    {
        return count($this->cards);
    }

    /**
     * Renders the cards as a comma-separated string, e.g. "A♠,K♥".
     */
    public function __toString(): string
    {
        return implode(',', $this->cards);
    }

    /**
     * Whether the stack has a capacity and it is fully occupied.
     */
    public function isFull(): bool
    {
        return $this->capacity !== null && count($this->cards) === $this->capacity;
    }

    /**
     * Whether the stack holds no cards.
     */
    public function isEmpty(): bool
    {
        return $this->cards === [];
    }

    /**
     * Stacks are the same if they have the same number of cards and the same cards in the same order.
     */
    public function isSame(self $other): bool
    {
        if ($this->count() !== $other->count()) {
            return false;
        }

        foreach ($this->cards as $i => $card) {
            if (!$card->equals($other->cards[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the stack holds at least $num cards.
     *
     * @throws \InvalidArgumentException If $num < 1.
     */
    public function enoughCards(int $num): bool
    {
        if ($num < 1) {
            throw new \InvalidArgumentException('Number of cards to check must be greater than 0');
        }

        return $this->count() >= $num;
    }

    /**
     * Check if the stack contains at least one match for each given card
     * (using equals()). A single stack card can satisfy multiple query
     * cards — use hasExactCards() when duplicates must match distinct
     * stack entries.
     */
    public function hasCards(PlayableCard ...$cards): bool
    {
        foreach ($cards as $card) {
            $found = false;
            foreach ($this->cards as $existing) {
                if (!$existing->equals($card)) {
                    continue;
                }

                $found = true;
                break;
            }
            if (!$found) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the stack has exactly the given cards, so if there are two of the same card and the stack contains only one,
     * it will return false.
     */
    public function hasExactCards(PlayableCard ...$cards): bool
    {
        if ($cards === []) {
            return true;
        }

        $matched = [];
        foreach ($cards as $card) {
            $found = false;
            foreach ($this->cards as $i => $existing) {
                if (array_key_exists($i, $matched)) {
                    continue;
                }
                if ($existing->equals($card)) {
                    $matched[$i] = true;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }

        return true;
    }

    /**
     * Append cards to the bottom of the stack.
     *
     * @throws \InvalidArgumentException If the cards would exceed capacity.
     */
    public function addCards(PlayableCard ...$cards): void
    {
        if ($this->capacity !== null && (count($this->cards) + count($cards)) > $this->capacity) {
            throw new \InvalidArgumentException('Adding these cards would exceed stack capacity');
        }
        $this->cards = array_merge($this->cards, array_values($cards));
    }

    /**
     * Remove the given cards, matched via hasExactCards() (distinct stack
     * entries per query card).
     *
     * @throws \InvalidArgumentException If the stack does not contain exactly these cards.
     */
    public function removeCards(PlayableCard ...$cards): void
    {
        if (!$this->hasExactCards(...$cards)) {
            throw new \InvalidArgumentException('Cards not found in stack');
        }

        $indicesToRemove = [];
        foreach ($cards as $card) {
            foreach ($this->cards as $index => $existing) {
                if (array_key_exists($index, $indicesToRemove)) {
                    continue;
                }
                if ($existing->equals($card)) {
                    $indicesToRemove[$index] = true;
                    break;
                }
            }
        }

        $remaining = [];
        foreach ($this->cards as $index => $existing) {
            if (array_key_exists($index, $indicesToRemove)) {
                continue;
            }
            $remaining[] = $existing;
        }
        $this->cards = $remaining;
    }

    /**
     * Return the top $num cards as a new Stack WITHOUT removing them.
     *
     * @throws \InvalidArgumentException If the stack has fewer than $num cards.
     */
    #[\NoDiscard]
    public function peek(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        return new self(array_slice($this->cards, 0, $num));
    }

    /**
     * Return the bottom $num cards as a new Stack WITHOUT removing them.
     *
     * @throws \InvalidArgumentException If the stack has fewer than $num cards.
     */
    #[\NoDiscard]
    public function peekBottom(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        return new self(array_slice($this->cards, -$num, $num));
    }

    /**
     * Return $num randomly chosen cards as a new Stack WITHOUT removing them.
     *
     * @throws \InvalidArgumentException If the stack has fewer than $num cards.
     */
    #[\NoDiscard]
    public function peekRandom(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        $keys = array_rand($this->cards, $num);
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        return new self(array_map(fn($key) => $this->cards[$key], $keys));
    }

    /**
     * Remove and return the top $num cards as a new Stack.
     *
     * @throws \InvalidArgumentException If the stack has fewer than $num cards.
     */
    #[\NoDiscard]
    public function takeCards(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        $cards = array_splice($this->cards, 0, $num);

        return new self($cards);
    }

    /**
     * Alias of takeCards(): remove and return the top $num cards.
     *
     * @throws \InvalidArgumentException If the stack has fewer than $num cards.
     */
    #[\NoDiscard]
    public function takeTop(int $num = 1): self
    {
        return $this->takeCards($num);
    }

    /**
     * Remove and return the bottom $num cards as a new Stack.
     *
     * @throws \InvalidArgumentException If the stack has fewer than $num cards.
     */
    #[\NoDiscard]
    public function takeBottom(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        $cards = array_splice($this->cards, -$num, $num);

        return new self($cards);
    }

    /**
     * Move the top $num cards to $target.
     *
     * Transactional: if $target rejects the cards (e.g. its capacity
     * would be exceeded), they are returned to this stack and the
     * exception propagates.
     *
     * @throws \InvalidArgumentException If this stack has fewer than $num cards or the target rejects them.
     */
    public function moveTo(self $target, int $num = 1): void
    {
        $cards = $this->takeCards($num);
        try {
            $target->addCards(...$cards);
        } catch (\InvalidArgumentException $e) {
            // Rollback: return the taken cards to the source so they
            // aren't lost when the target rejects them (e.g. full stack).
            $this->cards = array_merge($cards->cards, $this->cards);
            throw $e;
        }
    }

    /**
     * Move every card to $target. No-op if this stack is empty.
     *
     * @throws \InvalidArgumentException If the target rejects the cards.
     */
    public function moveAllTo(Stack $target): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $this->moveTo($target, $this->count());
    }

    /**
     * Move specific cards to $target, matched via hasExactCards().
     *
     * @throws \InvalidArgumentException If the cards are not all present in this stack.
     */
    public function moveCardsTo(Stack $target, PlayableCard ...$cards): void
    {
        if (!$this->hasExactCards(...$cards)) {
            throw new \InvalidArgumentException('Cards not found in stack');
        }

        $target->addCards(...$cards);
        $this->removeCards(...$cards);
    }

    /**
     * Sort the cards in ascending order by rank using the given RankOrder.
     */
    public function sortByRank(RankOrder $rankOrder): void
    {
        $this->sort(static fn(PlayableCard $a, PlayableCard $b) => $rankOrder->compare(
            $a->underlyingCard()->rank,
            $b->underlyingCard()->rank,
        ));
    }

    /**
     * The ranks of all cards in the stack, in stack order.
     *
     * @return list<Rank>
     */
    public function getRanks(): array
    {
        return array_map(static fn(PlayableCard $card) => $card->underlyingCard()->rank, $this->cards);
    }

    /**
     * Whether at least one card in the stack has the given rank.
     */
    public function hasRank(Rank $rank): bool
    {
        foreach ($this->cards as $card) {
            if ($card->underlyingCard()->rank === $rank) {
                return true;
            }
        }

        return false;
    }

    /**
     * The suits of all cards in the stack, in stack order.
     *
     * @return list<Suit>
     */
    public function getSuits(): array
    {
        return array_map(static fn(PlayableCard $card) => $card->underlyingCard()->suit, $this->cards);
    }

    /**
     * Sort the cards in place using a comparison callback, like usort().
     *
     * @param callable(PlayableCard, PlayableCard): int $callback
     */
    public function sort(callable $callback): void
    {
        usort($this->cards, $callback);
    }

    /**
     * Randomize the order of the cards in place.
     */
    public function shuffle(): void
    {
        shuffle($this->cards);
    }

    /**
     * Remove all cards from the stack.
     */
    public function clear(): void
    {
        $this->cards = [];
    }
}
