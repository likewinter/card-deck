<?php

namespace Likewinter\CardDeck;

class Dealer
{
    public const DRAW_SEQUENTIAL = 0;
    public const DRAW_ONE_BY_ONE = 1;
    public const DRAW_RANDOM = 2;

    private const DRAW_MODES = [
        self::DRAW_SEQUENTIAL,
        self::DRAW_ONE_BY_ONE,
        self::DRAW_RANDOM,
    ];

    private Stack $pile;

    public function __construct(
        private Deck $deck,
        /** @var list<Hand> */
        private array $hands = [],
        private int $drawMode = self::DRAW_SEQUENTIAL,
    ) {
        if (!in_array($drawMode, self::DRAW_MODES)) {
            throw new \InvalidArgumentException('Invalid draw mode');
        }

        foreach ($hands as $hand) {
            if (!is_a($hand, Hand::class)) {
                throw new \InvalidArgumentException('Hands must be instances of Hand');
            }
        }
        $this->pile = new Stack();
        $deck->shuffle();
    }

    public function getDeck(): Deck
    {
        return $this->deck;
    }

    public function getPile(): Stack
    {
        return $this->pile;
    }

    /** @return list<Hand> */
    public function getHands(): array
    {
        return $this->hands;
    }

    public function handExists(Hand $hand): void
    {
        if (!in_array($hand, $this->hands)) {
            throw new \InvalidArgumentException('Dealer does not have this hand');
        }
    }

    public function addHands(Hand ...$hands): void
    {
        $this->hands = array_merge($this->hands, array_values($hands));
    }

    public function removeHands(Hand ...$hands): void
    {
        foreach ($hands as $hand) {
            $this->handExists($hand);
            $this->pile->addCards(...$hand);
            $this->hands = array_diff($this->hands, [$hand]);
            unset($hand);
        }
    }

    public function drawAll(int $num = 1): void
    {
        if (count($this->hands) === 0) {
            throw new \InvalidArgumentException('No hands provided');
        }

        if ($num * count($this->hands) > $this->deck->count()) {
            throw new \InvalidArgumentException('Not enough cards in deck');
        }

        if ($this->drawMode === self::DRAW_ONE_BY_ONE) {
            for ($i = 0; $i < $num; $i++) {
                foreach ($this->hands as $hand) {
                    $hand->addCards(...$this->deck->takeTopCards(1));
                }
            }
        }

        if ($this->drawMode === self::DRAW_SEQUENTIAL) {
            foreach ($this->hands as $hand) {
                $hand->addCards(...$this->deck->takeTopCards($num));
            }
        }

        if ($this->drawMode === self::DRAW_RANDOM) {
            for ($i = 0; $i < $num; $i++) {
                foreach ($this->hands as $hand) {
                    $hand->addCards($this->deck->takeRandomCard());
                }
            }
        }
    }

    public function drawToHand(Hand $hand, int $num = 1): void
    {
        $this->handExists($hand);

        if ($num > $this->deck->count()) {
            throw new \InvalidArgumentException('Not enough cards in deck');
        }

        $hand->addCards(...$this->deck->takeTopCards($num));
    }

    public function discard(Hand $hand, Card ...$cards): void
    {
        $this->handExists($hand);
        $hand->removeCards(...$cards);
        $this->pile->addCards(...$cards);
    }
}
