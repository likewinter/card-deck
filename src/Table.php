<?php

namespace Likewinter\CardDeck;

/**
 * The card table: owns the deck, named hands, and pile for a game session.
 *
 * Table is the single orchestration point for card movement. Games register
 * named hands, draw cards into them, discard back to the pile, and reset
 * for a new round — all through this interface. The underlying Stacks are
 * private; games interact only through Table's behavioral methods.
 *
 * Hands are keyed by string identifiers chosen by the game:
 *   $table->addHand('dealer', new Hand());
 *   $table->addHand('north', new Hand(capacity: 13));
 *   $table->draw('north', 5);
 */
class Table
{
    private Stack $deck;
    private Stack $pile;

    /** @var array<string, Hand> */
    private array $hands = [];

    public function __construct(
        Stack $deck,
        private readonly DrawMode $drawMode = DrawMode::Sequential,
        bool $shuffle = false,
    ) {
        $this->deck = $deck;
        $this->pile = new Stack();
        if ($shuffle) {
            $this->deck->shuffle();
        }
    }

    // ── Hand registry ────────────────────────────────────────────────────

    public function addHand(string $name, Hand $hand): void
    {
        if (isset($this->hands[$name])) {
            throw new \InvalidArgumentException("Hand '{$name}' already exists");
        }
        $this->hands[$name] = $hand;
    }

    /**
     * Remove a named hand, moving its remaining cards to the pile.
     */
    public function removeHand(string $name): void
    {
        $hand = $this->hand($name);
        $hand->moveAllTo($this->pile);
        unset($this->hands[$name]);
    }

    public function hand(string $name): Hand
    {
        return $this->hands[$name]
            ?? throw new \InvalidArgumentException("Hand '{$name}' does not exist");
    }

    public function hasHand(string $name): bool
    {
        return isset($this->hands[$name]);
    }

    /** @return list<string> */
    public function handNames(): array
    {
        return array_keys($this->hands);
    }

    // ── Drawing ──────────────────────────────────────────────────────────

    /**
     * Draw cards from the deck into a named hand.
     * Respects the table's DrawMode (Random draws random cards).
     */
    public function draw(string $name, int $num = 1): void
    {
        $hand = $this->hand($name);

        if ($num > $this->deck->count()) {
            throw new \LogicException('Not enough cards in deck');
        }

        match ($this->drawMode) {
            DrawMode::Random => $this->drawRandomToHand($hand, $num),
            DrawMode::Sequential, DrawMode::OneByOne => $this->deck->moveTo($hand, $num),
        };
    }

    /**
     * Draw $num cards to every registered hand, respecting DrawMode.
     */
    public function drawAll(int $num = 1): void
    {
        if (empty($this->hands)) {
            throw new \LogicException('No hands registered');
        }

        if ($num * count($this->hands) > $this->deck->count()) {
            throw new \LogicException('Not enough cards in deck');
        }

        match ($this->drawMode) {
            DrawMode::OneByOne => $this->drawOneByOne($num),
            DrawMode::Sequential => $this->drawSequential($num),
            DrawMode::Random => $this->drawRandomAll($num),
        };
    }

    // ── Discarding ───────────────────────────────────────────────────────

    /**
     * Discard specific cards from a named hand to the pile.
     * With no cards argument, discards the entire hand.
     */
    public function discard(string $name, PlayableCard ...$cards): void
    {
        $hand = $this->hand($name);

        if (empty($cards)) {
            $hand->moveAllTo($this->pile);
            return;
        }

        $hand->moveCardsTo($this->pile, ...$cards);
    }

    /**
     * Collect cards from outside the table (e.g., played trick cards)
     * into the pile so reset() can recover them.
     */
    public function collectToPile(PlayableCard ...$cards): void
    {
        $this->pile->addCards(...$cards);
    }

    // ── Lifecycle ────────────────────────────────────────────────────────

    /**
     * Return all cards from hands and the pile to the deck.
     */
    public function reset(): void
    {
        foreach ($this->hands as $hand) {
            $hand->moveAllTo($this->deck);
        }
        $this->pile->moveAllTo($this->deck);
    }

    public function shuffle(): void
    {
        $this->deck->shuffle();
    }

    // ── Inspection ───────────────────────────────────────────────────────

    public function deckCount(): int
    {
        return $this->deck->count();
    }

    public function pileCount(): int
    {
        return $this->pile->count();
    }

    // ── Private draw strategies ──────────────────────────────────────────

    private function drawOneByOne(int $num): void
    {
        for ($i = 0; $i < $num; $i++) {
            foreach ($this->hands as $hand) {
                $this->deck->moveTo($hand, 1);
            }
        }
    }

    private function drawSequential(int $num): void
    {
        foreach ($this->hands as $hand) {
            $this->deck->moveTo($hand, $num);
        }
    }

    private function drawRandomAll(int $num): void
    {
        for ($i = 0; $i < $num; $i++) {
            foreach ($this->hands as $hand) {
                $this->drawRandomToHand($hand, 1);
            }
        }
    }

    private function drawRandomToHand(Hand $hand, int $num): void
    {
        for ($i = 0; $i < $num; $i++) {
            $this->deck->moveCardsTo($hand, ...$this->deck->peekRandom());
        }
    }
}
