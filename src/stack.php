<?php

namespace Likewinter\CardDeck;

trait Stack
{
    /** @var Card[] */
    private array $cards;

    public function hasCards(array $cards): bool
    {
        return count(array_intersect($cards, $this->cards)) === count($cards);
    }

    public function add(Card $card): void
    {
        $this->cards[] = $card;
    }

    public function addMany(array $cards): void
    {
        $this->cards = array_merge($this->cards, $cards);
    }

    public function remove(Card $card): Card
    {
        $this->removeMany([$card]);

        return $card;
    }

    public function removeMany(array $cards): array
    {
        if (!$this->hasCards($cards)) {
            throw new \InvalidArgumentException('Cards not found in stack');
        }

        $this->cards = array_filter($this->cards, fn ($c) => !in_array($c, $cards));

        return $cards;
    }

    public function takeTopCards(int $num = 1): array
    {
        return array_splice($this->cards, 0, $num);
    }

    public function takeBottomCards(int $num = 1): array
    {
        return array_splice($this->cards, -$num);
    }

    public function takeTopCard(): Card
    {
        return array_shift($this->cards);
    }

    public function takeBottomCard(): Card
    {
        return array_pop($this->cards);
    }

    public function takeRandomCard(): Card
    {
        return array_splice($this->cards, array_rand($this->cards), 1)[0];
    }

    public function clear(): array
    {
        $cards = $this->cards;
        $this->cards = [];

        return $cards;
    }

    public function count(): int
    {
        return count($this->cards);
    }

    public function isEmpty(): bool
    {
        return empty($this->cards);
    }

    public function getCards(): array
    {
        return $this->cards;
    }

    public function __toString(): string
    {
        return implode(', ', $this->cards);
    }
}
