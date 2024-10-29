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
        $ranks = array_map(fn ($rank) => $rank->getSymbol(), $this->getRanks());
        sort($ranks);

        return match ($ranks) {
            ['A', '2', '3', '4', '5'] => true,
            ['2', '3', '4', '5', '6'] => true,
            ['3', '4', '5', '6', '7'] => true,
            ['4', '5', '6', '7', '8'] => true,
            ['5', '6', '7', '8', '9'] => true,
            ['6', '7', '8', '9', '10'] => true,
            ['7', '8', '9', '10', 'J'] => true,
            ['8', '9', '10', 'J', 'Q'] => true,
            ['9', '10', 'J', 'Q', 'K'] => true,
            ['10', 'J', 'Q', 'K', 'A'] => true,
            default => false,
        };
    }

    public static function fromHand(Hand $hand): self
    {
        return new self(array_values([...$hand]));
    }
}
