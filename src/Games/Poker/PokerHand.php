<?php

namespace Likewinter\CardDeck\Games\Poker;

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Hand;

class PokerHand extends Hand
{
    public const HAND_SIZE = 5;
    public readonly HandRank $handRank;
    /** @var array<string, list<Card>> */
    public readonly array $rankSets;
    public readonly bool $isSameSuit;
    public readonly bool $isSequentialRank;

    public function __construct(
        /** @var list<Card> */
        public array $cards,
    ) {
        parent::__construct($cards, self::HAND_SIZE);
        $this->sortByRank();

        $this->rankSets = $this->getRankSets();
        $this->isSameSuit = $this->isSameSuit();
        $this->isSequentialRank = $this->isSequentialRank();
        $this->handRank = HandRank::getRankForHand($this);
    }

    /**
     * @return array<string,list<Card>>
     */
    protected function getRankSets(): array
    {
        $rankSets = [];
        foreach ($this->cards as $card) {
            $rankSets[$card->rank->getSymbol()][] = $card;
        }

        return $rankSets;
    }

    protected function isSameSuit(): bool
    {
        return count(array_unique(array_map(fn (Card $card) => $card->suit->getSymbol(), $this->cards))) === 1;
    }

    protected function isSequentialRank(): bool
    {
        $values = array_map(fn (Card $card) => $card->rank->value, $this->cards);
        sort($values);
        $values = array_values(array_unique($values));

        if (count($values) !== 5) {
            return false;
        }

        // Wheel: A-2-3-4-5 (Ace value 14 acts as 1)
        if ($values === [2, 3, 4, 5, 14]) {
            return true;
        }

        // Normal straight: 5 consecutive values
        for ($i = 1; $i < 5; $i++) {
            if ($values[$i] !== $values[$i - 1] + 1) {
                return false;
            }
        }

        return true;
    }

    public static function fromHand(Hand $hand): self
    {
        return new self(array_values([...$hand]));
    }
}
