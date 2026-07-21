<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Exceptions\DealerException;

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
        private Stack $deck,
        /** @var list<Hand> */
        private array $hands = [],
        /** @var value-of<self::DRAW_MODES> */
        private int $drawMode = self::DRAW_SEQUENTIAL,
        bool $shuffle = false,
    ) {
        $this->setDrawMode($drawMode);

        foreach ($hands as $hand) {
            if (!$hand instanceof Hand) {
                throw new DealerException('Hands must be instances of Hand');
            }
        }
        $this->pile = new Stack();
        if ($shuffle) {
            $deck->shuffle();
        }
    }

    public function getDeck(): Stack
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

    public function addHands(Hand ...$hands): void
    {
        $this->hands = array_merge($this->hands, array_values($hands));
    }

    public function removeHands(Hand ...$hands): void
    {
        foreach ($hands as $hand) {
            $this->validateHand($hand);
            $hand->moveAllTo($this->pile);
            $this->hands = array_values(array_filter(
                $this->hands,
                fn (Hand $existing) => $existing !== $hand
            ));
        }
    }

    public function drawAll(int $num = 1): void
    {
        if (count($this->hands) === 0) {
            throw new DealerException('No hands provided');
        }

        if ($num * count($this->hands) > $this->deck->count()) {
            throw new DealerException('Not enough cards in deck');
        }

        match ($this->drawMode) {
            self::DRAW_ONE_BY_ONE => $this->drawOneByOne($num),
            self::DRAW_SEQUENTIAL => $this->drawSequential($num),
            self::DRAW_RANDOM => $this->drawRandom($num),
        };
    }

    public function drawToHand(Hand $hand, int $num = 1): void
    {
        $this->validateHand($hand);

        if ($num > $this->deck->count()) {
            throw new DealerException('Not enough cards in deck');
        }

        match ($this->drawMode) {
            self::DRAW_RANDOM => $this->drawRandomToHand($hand, $num),
            default => $this->deck->moveTo($hand, $num),
        };
    }

    private function drawRandomToHand(Hand $hand, int $num): void
    {
        for ($i = 0; $i < $num; $i++) {
            $this->deck->moveCardsTo($hand, ...$this->deck->peekRandom());
        }
    }

    public function discard(Hand $hand, PlayableCard ...$cards): void
    {
        $this->validateHand($hand);

        if (empty($cards)) {
            $hand->moveAllTo($this->pile);
            return;
        }

        $hand->moveCardsTo($this->pile, ...$cards);
    }

    public function resetGame(): void
    {
        foreach ($this->hands as $hand) {
            $hand->moveAllTo($this->deck);
        }
        $this->pile->moveAllTo($this->deck);
    }

    private function validateHand(Hand $hand): void
    {
        if (!in_array($hand, $this->hands, true)) {
            throw new DealerException('Dealer does not have this hand');
        }
    }

    private function setDrawMode(int $drawMode): void
    {
        if (!in_array($drawMode, self::DRAW_MODES, true)) {
            throw new DealerException('Invalid draw mode');
        }
        $this->drawMode = $drawMode;
    }

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

    private function drawRandom(int $num): void
    {
        for ($i = 0; $i < $num; $i++) {
            foreach ($this->hands as $hand) {
                $this->deck->moveCardsTo($hand, ...$this->deck->peekRandom());
            }
        }
    }
}
