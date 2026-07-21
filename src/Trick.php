<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\Suit;

/**
 * A single trick in a trick-taking game: the cards played by each player
 * in one round of play, plus the rules to determine the winner.
 *
 * A trick starts when the first player leads a card. Each subsequent
 * player plays a card in turn order (tracked via PlayerRing). When all
 * players have played, winner() returns the index of the player whose
 * card won the trick, determined by SuitOrder + RankOrder.
 *
 * Trump and rank ordering are supplied at construction so the same Trick
 * works for Bridge, Spades, Hearts, Euchre, Pinochle, etc.
 */
final class Trick
{
    /** @var list<Card> Cards in the order they were played. */
    private array $cards = [];

    /** @var list<int> Player index for each played card, parallel to $cards. */
    private array $players = [];

    private ?Suit $leadSuit = null;

    public function __construct(
        private readonly SuitOrder $suitOrder,
        private readonly RankOrder $rankOrder,
        private readonly int $numPlayers,
    ) {
        if ($numPlayers < 2) {
            throw new \InvalidArgumentException('Trick requires at least 2 players');
        }
    }

    /**
     * A player plays a card. The first card sets the lead suit.
     */
    public function play(int $player, Card $card): void
    {
        if ($player < 0 || $player >= $this->numPlayers) {
            throw new \InvalidArgumentException("Player {$player} out of range");
        }
        if (count($this->cards) >= $this->numPlayers) {
            throw new \LogicException('Trick is complete; no more cards can be played');
        }
        if ($this->leadSuit === null) {
            $this->leadSuit = $card->suit;
        }

        $this->cards[] = $card;
        $this->players[] = $player;
    }

    /**
     * The suit that was led. Null if no cards have been played yet.
     */
    public function leadSuit(): ?Suit
    {
        return $this->leadSuit;
    }

    /**
     * Has every player played a card?
     */
    public function isComplete(): bool
    {
        return count($this->cards) === $this->numPlayers;
    }

    /**
     * Returns true if no cards have been played yet.
     */
    public function isEmpty(): bool
    {
        return empty($this->cards);
    }

    /**
     * Returns the index of the player who won the trick.
     *
     * @throws \LogicException if the trick is not complete.
     */
    public function winner(): int
    {
        if (!$this->isComplete()) {
            throw new \LogicException('Cannot determine winner of an incomplete trick');
        }

        $winnerIdx = 0;
        for ($i = 1, $n = count($this->cards); $i < $n; $i++) {
            if ($this->suitOrder->beats(
                $this->cards[$i],
                $this->cards[$winnerIdx],
                $this->leadSuit,
                $this->rankOrder
            )) {
                $winnerIdx = $i;
            }
        }

        return $this->players[$winnerIdx];
    }

    /**
     * Returns the cards played so far, in play order.
     *
     * @return list<Card>
     */
    public function cards(): array
    {
        return $this->cards;
    }

    /**
     * Returns the player indices who have played, in play order.
     *
     * @return list<int>
     */
    public function players(): array
    {
        return $this->players;
    }

    /**
     * Reset the trick for reuse (keeps the same suit/rank orders and
     * player count). The next call to play() starts a new lead.
     */
    public function clear(): void
    {
        $this->cards = [];
        $this->players = [];
        $this->leadSuit = null;
    }
}
