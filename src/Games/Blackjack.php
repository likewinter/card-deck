<?php

namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\{DeckBuilder, Hand, Table};
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

    private readonly Table $table;

    public function __construct(
        private readonly int $numPlayers = 1,
        int $numDecks = self::DEFAULT_NUM_DECKS,
        ?Table $table = null,
    ) {
        $this->table = $table ?? new Table(
            deck: DeckBuilder::standard52()->times($numDecks)->build(),
            shuffle: true,
        );

        $this->table->addHand('dealer', new Hand());

        for ($i = 0; $i < $this->numPlayers; $i++) {
            $this->table->addHand("player-{$i}", new Hand());
        }
    }

    /**
     * Deal 2 cards to each player and the dealer.
     */
    public function deal(): void
    {
        $this->table->drawAll(2);
    }

    /**
     * Player draws a card (hit).
     */
    public function hit(int $player): void
    {
        $this->table->draw("player-{$player}", 1);
    }

    /**
     * Dealer plays: hits until reaching DEALER_STANDS_ON or higher.
     */
    public function dealerPlay(): void
    {
        while (BlackjackHand::fromHand($this->table->hand('dealer'))->value() < self::DEALER_STANDS_ON) {
            $this->table->draw('dealer', 1);
        }
    }

    /**
     * Returns the dealer's hand as a BlackjackHand.
     */
    public function dealerCards(): BlackjackHand
    {
        return BlackjackHand::fromHand($this->table->hand('dealer'));
    }

    /**
     * Returns a player's hand as a BlackjackHand.
     */
    public function playerCards(int $player): BlackjackHand
    {
        return BlackjackHand::fromHand($this->table->hand("player-{$player}"));
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
        $this->table->reset();
        $this->table->shuffle();
    }
}
