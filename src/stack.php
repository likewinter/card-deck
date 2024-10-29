<?php

namespace Likewinter\CardDeck;

use ArrayIterator;
use Iterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, Card>
 */
class Stack implements IteratorAggregate
{
    public function __construct(
        /** @var list<Card> */
        protected array $cards = [],
        protected readonly ?int $stackLimit = null
    ) {
        if ($stackLimit !== null && $stackLimit < 1) {
            throw new \InvalidArgumentException('Stack limit must be greater than 0');
        }
        if ($stackLimit !== null && count($cards) > $stackLimit) {
            throw new \InvalidArgumentException('Stack limit exceeded');
        }

        foreach ($cards as $card) {
            if (!$card instanceof Card) {
                throw new \InvalidArgumentException('All cards must be instances of Card');
            }
        }
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->cards);
    }

    public function hasCards(Card ...$cards): bool
    {
        return count(array_intersect($cards, $this->cards)) === count($cards);
    }

    public function addCards(Card ...$cards): void
    {
        $this->cards = array_merge($this->cards, array_values($cards));
    }

    public function removeCards(Card ...$cards): void
    {
        if (!$this->hasCards(...$cards)) {
            throw new \InvalidArgumentException('Cards not found in stack');
        }

        $this->cards = array_filter($this->cards, fn ($c) => !in_array($c, $cards));
    }

    public function takeTopCards(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        return new self(array_splice($this->cards, 0, $num));
    }

    public function takeBottomCards(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        return new self(array_splice($this->cards, -$num));
    }

    public function takeRandomCard(): Card
    {
        if ($this->isEmpty()) {
            throw new \InvalidArgumentException('Stack is empty');
        }

        return array_splice($this->cards, array_rand($this->cards), 1)[0];
    }

    public function sort(callable $callback): void
    {
        usort($this->cards, $callback);
    }

    public function clear(): void
    {
        $this->cards = [];
    }

    public function count(): int
    {
        return count($this->cards);
    }

    public function isEmpty(): bool
    {
        return empty($this->cards);
    }

    public function enoughCards(int $num): bool
    {
        if ($num < 1) {
            throw new \InvalidArgumentException('Number of cards to check must be greater than 0');
        }

        return $this->count() >= $num;
    }

    public function __toString(): string
    {
        return implode(',', $this->cards);
    }
}
