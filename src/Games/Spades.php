<?php

namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\{DeckBuilder, Hand, RankOrder, SuitOrder, Table, Trick};
use Likewinter\CardDeck\Card\{Rank, Suit};

/**
 * Spades — a trick-taking game where spades are always trump.
 *
 * Demonstrates: SuitOrder with a trump suit, Trick with enforced
 * turn order, PlayerRing (via Trick), RankOrder::poker() for
 * within-suit comparison, and trick-counting scoring.
 *
 * 4 players, 13 cards each, 13 tricks per hand.
 */
readonly class Spades
{
    public const NUM_PLAYERS = 4;
    public const CARDS_PER_PLAYER = 13;

    private readonly Table $table;
    private readonly SuitOrder $suitOrder;
    private readonly RankOrder $rankOrder;

    public function __construct(?Table $table = null)
    {
        $this->table = $table ?? new Table(
            deck: DeckBuilder::standard52()->build(),
            shuffle: true,
        );

        for ($i = 0; $i < self::NUM_PLAYERS; $i++) {
            $this->table->addHand("player-{$i}", new Hand(capacity: self::CARDS_PER_PLAYER));
        }

        $this->suitOrder = SuitOrder::suit(Suit::Spades);
        $this->rankOrder = RankOrder::poker();
    }

    /**
     * Deal 13 cards to each player.
     */
    public function deal(): void
    {
        $this->table->drawAll(self::CARDS_PER_PLAYER);
    }

    /**
     * Returns a player's hand.
     */
    public function hand(int $player): Hand
    {
        return $this->table->hand("player-{$player}");
    }

    /**
     * Play a full hand (13 tricks). Each trick, every player plays
     * one card from their hand. The winner of each trick leads the
     * next. Returns the tricks-won count per player.
     *
     * @param callable(int $player, Hand $hand, ?Suit $leadSuit): \Likewinter\CardDeck\Card $chooseCard
     *        A callback that picks a card for the given player.
     * @return array<int, int> Tricks won per player.
     */
    public function playHand(callable $chooseCard): array
    {
        $tricksWon = array_fill(0, self::NUM_PLAYERS, 0);
        $leader = 0;

        for ($t = 0; $t < self::CARDS_PER_PLAYER; $t++) {
            $trick = new Trick(
                suitOrder: $this->suitOrder,
                rankOrder: $this->rankOrder,
                numPlayers: self::NUM_PLAYERS,
                startingPlayer: $leader,
            );

            for ($p = 0; $p < self::NUM_PLAYERS; $p++) {
                $player = $trick->currentPlayer();
                $hand = $this->hand($player);
                $leadSuit = $trick->leadSuit();

                $card = $chooseCard($player, $hand, $leadSuit);
                $hand->removeCards($card);
                $trick->play($card);
            }

            $winner = $trick->winner();
            $tricksWon[$winner]++;
            $leader = $winner;

            $this->table->collectToPile(...$trick->cards());
        }

        return $tricksWon;
    }

    /**
     * Reset for a new hand: return all cards to the deck and reshuffle.
     */
    public function reset(): void
    {
        $this->table->reset();
        $this->table->shuffle();
    }
}
