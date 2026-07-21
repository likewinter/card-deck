<?php

namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\{Dealer, DeckBuilder, Hand};
use Likewinter\CardDeck\Games\Blackjack\BlackjackHand;

/**
 * Blackjack (Twenty-One) — a hand-value game where players race the
 * dealer to 21 without busting.
 *
 * Demonstrates: RankOrder::blackjack(), multi-deck shoe via
 * DeckBuilder::times(), additive hand evaluation (vs Poker's
 * classificatory evaluation).
 */
readonly class Blackjack
{
    private const DEFAULT_NUM_DECKS = 6;
    private const DEALER_STANDS_ON = 17;

    private readonly Dealer $dealer;
    private readonly Hand $dealerHand;

    public function __construct(
        private readonly int $numPlayers = 1,
        int $numDecks = self::DEFAULT_NUM_DECKS,
        ?Dealer $dealer = null,
    ) {
        $this->dealer = $dealer ?? new Dealer(
            deck: DeckBuilder::standard52()->times($numDecks)->build(),
            shuffle: true,
        );

        $this->dealerHand = new Hand();
        $this->dealer->addHands($this->dealerHand);

        for ($i = 0; $i < $this->numPlayers; $i++) {
            $this->dealer->addHands(new Hand());
        }
    }

    /**
     * Deal 2 cards to each player and the dealer.
     */
    public function deal(): void
    {
        $this->dealer->drawAll(2);
    }

    /**
     * Player draws a card (hit).
     */
    public function hit(int $player): void
    {
        $hand = $this->playerHand($player);
        $this->dealer->drawToHand($hand, 1);
    }

    /**
     * Dealer plays: hits until reaching DEALER_STANDS_ON or higher.
     */
    public function dealerPlay(): void
    {
        while (BlackjackHand::fromHand($this->dealerHand)->value() < self::DEALER_STANDS_ON) {
            $this->dealer->drawToHand($this->dealerHand, 1);
        }
    }

    /**
     * Returns the dealer's hand as a BlackjackHand.
     */
    public function dealerCards(): BlackjackHand
    {
        return BlackjackHand::fromHand($this->dealerHand);
    }

    /**
     * Returns a player's hand as a BlackjackHand.
     */
    public function playerCards(int $player): BlackjackHand
    {
        return BlackjackHand::fromHand($this->playerHand($player));
    }

    /**
     * Determine the outcome for a player against the dealer.
     *
     * Returns: 'win', 'lose', 'push', or 'blackjack'.
     */
    public function outcome(int $player): string
    {
        $playerHand = $this->playerCards($player);
        $dealerHand = $this->dealerCards();

        if ($playerHand->isBust()) {
            return 'lose';
        }

        if ($playerHand->isBlackjack() && !$dealerHand->isBlackjack()) {
            return 'blackjack';
        }

        if ($dealerHand->isBust()) {
            return 'win';
        }

        if ($playerHand->isBlackjack() && $dealerHand->isBlackjack()) {
            return 'push';
        }

        $pv = $playerHand->value();
        $dv = $dealerHand->value();

        return match (true) {
            $pv > $dv => 'win',
            $pv < $dv => 'lose',
            default => 'push',
        };
    }

    /**
     * Reset for a new round: return all cards to the shoe and reshuffle.
     */
    public function reset(): void
    {
        $this->dealer->resetGame();
        $this->dealer->getDeck()->shuffle();
    }

    private function playerHand(int $player): Hand
    {
        // +1 because the dealer hand is at index 0
        $hands = $this->dealer->getHands();
        $index = $player + 1;

        if ($index < 0 || $index >= count($hands)) {
            throw new \InvalidArgumentException("Player {$player} does not exist");
        }

        return $hands[$index];
    }
}
