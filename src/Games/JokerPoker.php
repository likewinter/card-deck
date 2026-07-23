<?php

namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\{Card, DeckBuilder, Stack, Table, Wildcard};
use Likewinter\CardDeck\Card\{Rank, Suit};
use Likewinter\CardDeck\Games\Poker\PokerHand;

/**
 * Joker Poker — 5-card poker with jokers as wildcards.
 *
 * Demonstrates: Wildcard decorator for joker substitution, assignment
 * tracking (assign/unassign), underlyingCard() resolving to the
 * assigned card for PokerHand classification, and Wildcard equality
 * in Stack operations (removeCards for re-assignment).
 *
 * 2+ players, 5 cards each, 54-card deck (52 + 2 jokers).
 */
readonly class JokerPoker
{
    private const HAND_SIZE = 5;

    private Table $table;

    public function __construct(
        private int $numHands = 2,
        ?Table $table = null,
    ) {
        $this->table = $table ?? new Table(
            deck: self::buildWildDeck(),
            shuffle: true,
        );

        for ($i = 0; $i < $this->numHands; $i++) {
            $this->table->addHand("hand-{$i}", new Stack(capacity: self::HAND_SIZE));
        }
    }

    /**
     * Build a 54-card deck with jokers wrapped as Wildcard.
     */
    private static function buildWildDeck(): Stack
    {
        $deck = DeckBuilder::standard52WithJokers(2)->build();
        $cards = array_map(
            fn($c) => $c->isJoker() ? new Wildcard($c->underlyingCard()) : $c,
            [...$deck],
        );

        return new Stack($cards, count($cards));
    }

    public function deal(): void
    {
        $this->table->drawAll(self::HAND_SIZE);
    }

    public function hand(int $index): Stack
    {
        return $this->table->hand("hand-{$index}");
    }

    /**
     * Assign the first unassigned wildcard in a hand to represent a card.
     */
    public function assignWildcard(int $handIndex, Card $represents): void
    {
        $hand = $this->hand($handIndex);

        foreach ([...$hand] as $card) {
            if ($card instanceof Wildcard && $card->isUnassigned()) {
                $hand->removeCards($card);
                $hand->addCards($card->assign($represents));
                return;
            }
        }

        throw new \LogicException('No unassigned wildcard in hand');
    }

    public function hasUnassignedWildcards(int $handIndex): bool
    {
        foreach ([...$this->hand($handIndex)] as $card) {
            if ($card instanceof Wildcard && $card->isUnassigned()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate a hand as a PokerHand. All wildcards must be assigned first.
     */
    public function pokerHand(int $index): PokerHand
    {
        if ($this->hasUnassignedWildcards($index)) {
            throw new \LogicException('Assign all wildcards before evaluating');
        }

        return PokerHand::fromHand($this->hand($index));
    }

    /**
     * Returns the winning PokerHand(s). All wildcards must be assigned.
     *
     * @return list<PokerHand>
     */
    public function winners(): array
    {
        $pokerHands = [];
        foreach ($this->table->handNames() as $name) {
            $hand = $this->table->hand($name);
            if ($hand->count() === self::HAND_SIZE) {
                $pokerHands[] = PokerHand::fromHand($hand);
            }
        }

        if (empty($pokerHands)) {
            return [];
        }

        $best = $pokerHands[0];
        $winners = [$best];

        for ($i = 1, $n = count($pokerHands); $i < $n; $i++) {
            $cmp = $pokerHands[$i]->compare($best);
            if ($cmp > 0) {
                $best = $pokerHands[$i];
                $winners = [$best];
            } elseif ($cmp === 0) {
                $winners[] = $pokerHands[$i];
            }
        }

        return $winners;
    }

    /**
     * Reset for a new round: unassign wildcards, return all cards, reshuffle.
     */
    public function reset(): void
    {
        foreach ($this->table->handNames() as $name) {
            $hand = $this->table->hand($name);
            foreach ([...$hand] as $card) {
                if ($card instanceof Wildcard && $card->isAssigned()) {
                    $hand->removeCards($card);
                    $hand->addCards($card->unassign());
                }
            }
        }

        $this->table->reset();
        $this->table->shuffle();
    }
}
