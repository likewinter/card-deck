<?php

namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\{Dealer, DeckBuilder, Hand};
use Likewinter\CardDeck\Games\Poker\PokerHand;

readonly class Poker
{
    private const DEFAULT_NUM_HANDS = 3;
    private const MIN_HANDS = 2;
    private const MAX_HANDS = 5;

    private readonly Dealer $dealer;

    public function __construct(
        private readonly int $numHands = self::DEFAULT_NUM_HANDS,
        ?Dealer $dealer = null,
    ) {
        $this->dealer = $dealer ?? new Dealer(
            deck: DeckBuilder::standard52()->build(),
            shuffle: true,
        );

        $this->validateConfig();

        for ($i = 0; $i < $this->numHands; $i++) {
            $this->dealer->addHands(new Hand(capacity: PokerHand::HAND_SIZE));
        }
    }

    private function validateConfig(): void
    {
        if ($this->numHands < self::MIN_HANDS || $this->numHands > self::MAX_HANDS) {
            throw new \InvalidArgumentException(
                sprintf('Number of hands must be between %d and %d', self::MIN_HANDS, self::MAX_HANDS)
            );
        }
    }

    public function deal(): void
    {
        $this->dealer->drawAll(PokerHand::HAND_SIZE);
    }

    /**
     * Returns the current dealt hands as PokerHand objects, in player
     * order. Only hands with a full 5 cards are included — before
     * dealing (or after reset), this returns an empty list.
     *
     * @return list<PokerHand>
     */
    public function hands(): array
    {
        $pokerHands = [];
        foreach ($this->dealer->getHands() as $hand) {
            if ($hand->count() === PokerHand::HAND_SIZE) {
                $pokerHands[] = PokerHand::fromHand($hand);
            }
        }

        return $pokerHands;
    }

    /**
     * Resets the game for a new round: returns all cards from hands and
     * the pile to the deck and reshuffles. Hands become empty.
     */
    public function reset(): void
    {
        $this->dealer->resetGame();
        $this->dealer->getDeck()->shuffle();
    }

    /**
     * Returns the winning PokerHand(s) from the currently dealt hands.
     * If multiple dealt hands tie for the best rank, all winners are
     * returned.
     *
     * @return list<PokerHand>
     */
    public function winners(): array
    {
        $pokerHands = $this->hands();

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
}
