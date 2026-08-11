<?php

declare(strict_types=1);

namespace Likewinter\CardDeck;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\Card\Suit;

/**
 * @implements IteratorAggregate<int, PlayableCard>
 */
class Stack implements IteratorAggregate, \Countable
{
    /** @var list<PlayableCard> */
    protected array $cards = [];

    /**
     * @param iterable<mixed, mixed> $cards Any iterable of items; each must implement PlayableCard (validated at runtime).
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

    #[\NoDiscard]
    public static function fromString(string $string, ?int $capacity = null): self
    {
        if ($string === '') {
            return new self();
        }

        $cards = explode(',', $string);

        return new self(array_map(Card::fromString(...), $cards), $capacity);
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
        return implode(',', $this->cards);
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && count($this->cards) === $this->capacity;
    }

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

    public function addCards(PlayableCard ...$cards): void
    {
        if ($this->capacity !== null && (count($this->cards) + count($cards)) > $this->capacity) {
            throw new \InvalidArgumentException('Adding these cards would exceed stack capacity');
        }
        $this->cards = array_merge($this->cards, array_values($cards));
    }

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

    #[\NoDiscard]
    public function peek(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        return new self(array_slice($this->cards, 0, $num));
    }

    #[\NoDiscard]
    public function peekBottom(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        return new self(array_slice($this->cards, -$num, $num));
    }

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

    #[\NoDiscard]
    public function takeCards(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        $cards = array_splice($this->cards, 0, $num);

        return new self($cards);
    }

    #[\NoDiscard]
    public function takeTop(int $num = 1): self
    {
        return $this->takeCards($num);
    }

    #[\NoDiscard]
    public function takeBottom(int $num = 1): self
    {
        if (!$this->enoughCards($num)) {
            throw new \InvalidArgumentException('Not enough cards in stack');
        }

        $cards = array_splice($this->cards, -$num, $num);

        return new self($cards);
    }

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
            throw new \InvalidArgumentException('Cards not found in stack');
        }

        $target->addCards(...$cards);
        $this->removeCards(...$cards);
    }

    public function sortByRank(RankOrder $rankOrder): void
    {
        $this->sort(static fn(PlayableCard $a, PlayableCard $b) => $rankOrder->compare(
            $a->underlyingCard()->rank,
            $b->underlyingCard()->rank,
        ));
    }

    /** @return list<Rank> */
    public function getRanks(): array
    {
        return array_map(static fn(PlayableCard $card) => $card->underlyingCard()->rank, $this->cards);
    }

    public function hasRank(Rank $rank): bool
    {
        foreach ($this->cards as $card) {
            if ($card->underlyingCard()->rank === $rank) {
                return true;
            }
        }

        return false;
    }

    /** @return list<Suit> */
    public function getSuits(): array
    {
        return array_map(static fn(PlayableCard $card) => $card->underlyingCard()->suit, $this->cards);
    }

    /**
     * @param callable(PlayableCard, PlayableCard): int $callback
     */
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
