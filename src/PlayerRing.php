<?php

namespace Likewinter\CardDeck;

/**
 * Turn order for a game: a rotating pointer over a fixed list of players.
 *
 * Most card games use clockwise turn order with an optional 'skip' or
 * 'reverse' modifier. This primitive captures the common case and lets
 * games query 'whose turn is it?' and 'advance to the next player'.
 *
 * Players are identified by integer index (0-based). Games map these to
 * their own player representation (names, Hand objects, etc.).
 */
final class PlayerRing
{
    /**
     * @param int $numPlayers Number of players (>= 2).
     * @param int $startingPlayer Index of the player who acts first.
     */
    public function __construct(
        public readonly int $numPlayers,
        public readonly int $startingPlayer = 0,
    ) {
        if ($numPlayers < 2) {
            throw new \InvalidArgumentException('PlayerRing requires at least 2 players');
        }
        if ($startingPlayer < 0 || $startingPlayer >= $numPlayers) {
            throw new \InvalidArgumentException('Starting player index out of range');
        }
        $this->current = $startingPlayer;
    }

    private int $current;

    /**
     * Returns the index of the player whose turn it is.
     */
    public function current(): int
    {
        return $this->current;
    }

    /**
     * Advance to the next player and return their index.
     */
    public function next(): int
    {
        $this->current = ($this->current + 1) % $this->numPlayers;

        return $this->current;
    }

    /**
     * Returns the index of the next player (without moving the pointer).
     */
    public function peekNext(): int
    {
        return ($this->current + 1) % $this->numPlayers;
    }

    /**
     * Reset to the starting player.
     */
    public function reset(): void
    {
        $this->current = $this->startingPlayer;
    }

    /**
     * Set the current player to a specific index.
     */
    public function setCurrent(int $player): void
    {
        if ($player < 0 || $player >= $this->numPlayers) {
            throw new \InvalidArgumentException('Player index out of range');
        }
        $this->current = $player;
    }

    /**
     * Advance N steps around the ring.
     */
    public function advance(int $steps): int
    {
        if ($steps < 0) {
            throw new \InvalidArgumentException('Steps must be non-negative');
        }
        $this->current = ($this->current + $steps) % $this->numPlayers;

        return $this->current;
    }
}
