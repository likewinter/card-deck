<?php

namespace Likewinter\CardDeck;

use ArrayIterator;
use Iterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, PlayableCard>
 */
class Stack implements IteratorAggregate, \Countable
{
    public function __construct(
        /** @var list<PlayableCard> */
        protected array $cards = [],
        public readonly ?int $capacity = null
    ) {
        if ($capacity !== null && $capacity < 1) {
            throw new \InvalidArgumentException(
                "Stack capacity must be greater than 0"
            );
        }
        if ($capacity !== null && count($cards) > $capacity) {
            throw new \InvalidArgumentException("Stack capacity exceeded");
        }

        foreach ($cards as $card) {
            if (!$card instanceof PlayableCard) {
                throw new \InvalidArgumentException(
                    "All cards must implement PlayableCard"
                );
            }
        }
    }

    public static function fromString(string $string, ?int $capacity = null): self
    {
        if (empty($string)) {
            return new self();
        }

        $cards = explode(",", $string);

        return new self(array_map(fn(string $card) => Card::fromString($card), $cards), $capacity);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->cards);
    }

    public function count(): int
    {
        return count($this->cards);
    }

    public function __toString(): string
    {
        return implode(",", $this->cards);
    }

    public function isFull(): bool
    {
        return $this->capacity !== null &&
            count($this->cards) === $this->capacity;
    }

    public function isEmpty(): bool
    {
        return empty($this->cards);
    }

    /**
     * Stacks are the same if they have the same number of cards and the same cards in the same order.
     * 
     * @param self $other
     */
    public function isSame(self $other): bool
    {
        return (string)$this === (string)$other;
    }

    public function enoughCards(int $num): bool
    {
        if ($num < 1) {
            throw new \InvalidArgumentException(
                "Number of cards to check must be greater than 0"
            );
        }

        return $this->count() >= $num;
    }

    /**
     * Check if any of given cards are in the stack, so if there are two duplicates, it will return true beside
     * the fact that the stack has only one card.
     */
    public function hasCards(PlayableCard ...$cards): bool
    {
        foreach ($cards as $card) {
            if (!in_array($card, $this->cards)) {
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
        if (empty($cards)) {
            return true;
        }

        $stackFrequency = array_count_values(array_map(fn(PlayableCard $card) => (string)$card, $this->cards));
        $cardsFrequency = array_count_values(array_map(fn(PlayableCard $card) => (string)$card, $cards));

        foreach ($cardsFrequency as $card => $frequency) {
            if (!isset($stackFrequency[$card]) || $stackFrequency[$card] < $frequency) {
                return false; // card not found or not enough of it in the stack
            }
        }

        return true;
    }

    public function addCards(PlayableCard ...$cards): void
    {
        if (
            $this->capacity !== null &&
            count($this->cards) + count($cards) > $this->capacity
        ) {
            throw new \InvalidArgumentException(
                "Adding these cards would exceed stack capacity"
            );
        }
        $this->cards = array_merge($this->cards, array_values($cards));
    }

    public function removeCards(PlayableCard ...$cards): void
    {
        if (!$this->hasExactCards(...$cards)) {
            throw new \InvalidArgumentException("Cards not found in stack");
        }

        $indicesToRemove = [];
        foreach ($cards as $card) {
            foreach ($this->cards as $index => $existing) {
                if (isset($indicesToRemove[$index])) {
                    continue;
                }
                if ((string) $existing === (string) $card) {
                    $indicesToRemove[$index] = true;
                    break;
                }
            }
        }

        foreach (array_keys($indicesToRemove) as $index) {
            unset($this->cards[$index]);
        }
        $this->cards = array_values($this->cards);
    }

    public function peek(int $num = 1, bool $fromTop = true): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException("Not enough cards in stack");
        }

        return new self(array_slice($this->cards, $fromTop ? 0 : -$num, $num));
    }

    public function peekRandom(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException("Not enough cards in stack");
        }

        $keys = array_rand($this->cards, $num);
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        return new self(array_map(fn($key) => $this->cards[$key], $keys));
    }

    public function takeCards(int $num = 1, bool $fromTop = true): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException("Not enough cards in stack");
        }

        $cards = array_splice($this->cards, $fromTop ? 0 : -$num, $num);

        return new self($cards);
    }

    public function takeTop(int $num = 1): self
    {
        return $this->takeCards($num, true);
    }

    public function takeBottom(int $num = 1): self
    {
        return $this->takeCards($num, false);
    }

    public function moveTo(
        self $target,
        int $num = 1,
        bool $fromTop = true
    ): void {
        $cards = $this->takeCards($num, $fromTop);
        try {
            $target->addCards(...$cards);
        } catch (\InvalidArgumentException $e) {
            // Rollback: return the taken cards to the source so they
            // aren't lost when the target rejects them (e.g. full stack).
            $this->cards = $fromTop
                ? array_merge($cards->cards, $this->cards)
                : array_merge($this->cards, $cards->cards);
            throw $e;
        }
    }

    public function moveAllTo(Stack $target): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $this->moveTo($target, $this->count());
    }

    public function moveCardsTo(Stack $target, PlayableCard ...$cards): void
    {
        if (!$this->hasExactCards(...$cards)) {
            throw new \InvalidArgumentException("Cards not found in stack");
        }

        $target->addCards(...$cards);
        $this->removeCards(...$cards);
    }

    public function sort(callable $callback): void
    {
        usort($this->cards, $callback);
    }

    public function shuffle(): void
    {
        shuffle($this->cards);
    }

    public function clear(): void
    {
        $this->cards = [];
    }
}
