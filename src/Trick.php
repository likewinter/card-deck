<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\Suit;

/**
 * A single trick in a trick-taking game: the cards played by each player
 * in one round of play, plus the rules to determine the winner.
 *
 * A trick starts when the current player leads a card. Each subsequent
 * player plays in turn order (enforced internally via PlayerRing). When
 * all players have played, winner() returns the index of the player
 * whose card won the trick, determined by the SuitOrder.
 *
 * Trump rules, lead-suit rules, and rank ordering are all carried by
 * the SuitOrder supplied at construction.
 */
final class Trick
{
    /** @var list<Card> Cards in the order they were played. */
    private array $cards = [];

    /** @var list<int> Player index for each played card, parallel to $cards. */
    private array $players = [];

    private ?Suit $leadSuit = null;

    private PlayerRing $ring;

    public function __construct(
        private readonly SuitOrder $suitOrder,
        private readonly int $numPlayers,
        int $startingPlayer = 0,
    ) {
        if ($numPlayers < 2) {
            throw new \InvalidArgumentException('Trick requires at least 2 players');
        }
        $this->ring = new PlayerRing($numPlayers, $startingPlayer);
    }

    /**
     * The current player plays a card. Turn order is enforced — playing
     * out of turn throws. The first card sets the lead suit.
     */
    public function play(Card $card): void
    {
        if (count($this->cards) >= $this->numPlayers) {
            throw new \LogicException('Trick is complete; no more cards can be played');
        }
        if ($this->leadSuit === null) {
            $this->leadSuit = $card->suit;
        }

        $this->cards[] = $card;
        $this->players[] = $this->ring->current();
        $this->ring->next();
    }

    /**
     * Returns the index of the player whose turn it is.
     */
    public function currentPlayer(): int
    {
        return $this->ring->current();
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
                $this->leadSuit
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
     * Reset the trick for reuse (keeps the same suit/rank orders and
     * player count). The next call to play() starts a new lead.
     *
     * Pass $nextLeader to set who leads the next trick (typically the
     * winner of this trick). Defaults to player 0.
     */
    public function clear(?int $nextLeader = null): void
    {
        $this->cards = [];
        $this->players = [];
        $this->leadSuit = null;
        $this->ring->reset();
        if ($nextLeader !== null) {
            $this->ring->setCurrent($nextLeader);
        }
    }
}
