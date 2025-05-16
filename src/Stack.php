<?php

namespace Likewinter\CardDeck;

use ArrayIterator;
use Iterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, Card>
 */
class Stack implements IteratorAggregate, \Countable
{
    public function __construct(
        /** @var list<Card> */
        protected array $cards = [],
        protected readonly ?int $stackLimit = null
    ) {
        if ($stackLimit !== null && $stackLimit < 1) {
            throw new \InvalidArgumentException(
                "Stack limit must be greater than 0"
            );
        }
        if ($stackLimit !== null && count($cards) > $stackLimit) {
            throw new \InvalidArgumentException("Stack limit exceeded");
        }

        foreach ($cards as $card) {
            if (!$card instanceof Card) {
                throw new \InvalidArgumentException(
                    "All cards must be instances of Card"
                );
            }
        }
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
        return $this->stackLimit !== null &&
            count($this->cards) === $this->stackLimit;
    }

    public function isEmpty(): bool
    {
        return empty($this->cards);
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

    public function hasCards(Card ...$cards): bool
    {
        return count(array_intersect($cards, $this->cards)) === count($cards);
    }

    public function addCards(Card ...$cards): void
    {
        if (
            $this->stackLimit !== null &&
            count($this->cards) + count($cards) > $this->stackLimit
        ) {
            throw new \InvalidArgumentException(
                "Adding these cards would exceed stack limit"
            );
        }
        $this->cards = array_merge($this->cards, array_values($cards));
    }

    public function removeCards(Card ...$cards): void
    {
        if (!$this->hasCards(...$cards)) {
            throw new \InvalidArgumentException("Cards not found in stack");
        }

        $this->cards = array_filter(
            $this->cards,
            fn($c) => !in_array($c, $cards)
        );
    }

    public function peek(int $num = 1, bool $fromTop = true): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException("Not enough cards in stack");
        }

        return new self(array_slice($this->cards, $fromTop ? 0 : -$num, $num));
    }

    public function peekRandom(): Card
    {
        if ($this->isEmpty()) {
            throw new \InvalidArgumentException("Stack is empty");
        }

        return array_slice($this->cards, array_rand($this->cards), 1)[0];
    }

    public function moveCardTo(Stack $target, Card $card): void
    {
        if (!$this->hasCards($card)) {
            throw new \InvalidArgumentException("Card not found in stack");
        }

        $target->addCards($card);
        $this->removeCards($card);
    }

    public function moveTo(
        Stack $target,
        int $num = 1,
        bool $fromTop = true
    ): void {
        $cards = $this->peek($num, $fromTop);
        $target->addCards(...$cards);
        $this->removeCards(...$cards);
    }

    public function moveAllTo(Stack $target): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $target->addCards(...$this->cards);
        $this->clear();
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
