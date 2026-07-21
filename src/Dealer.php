<?php

namespace Likewinter\CardDeck;

class Dealer
{
    private Stack $pile;

    public function __construct(
        private Stack $deck,
        /** @var list<Hand> */
        private array $hands = [],
        private DrawMode $drawMode = DrawMode::Sequential,
        bool $shuffle = false,
    ) {
        foreach ($hands as $hand) {
            if (!$hand instanceof Hand) {
                throw new \InvalidArgumentException('Hands must be instances of Hand');
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
            throw new \LogicException('No hands provided');
        }

        if ($num * count($this->hands) > $this->deck->count()) {
            throw new \LogicException('Not enough cards in deck');
        }

        match ($this->drawMode) {
            DrawMode::OneByOne => $this->drawOneByOne($num),
            DrawMode::Sequential => $this->drawSequential($num),
            DrawMode::Random => $this->drawRandom($num),
        };
    }

    public function drawToHand(Hand $hand, int $num = 1): void
    {
        $this->validateHand($hand);

        if ($num > $this->deck->count()) {
            throw new \LogicException('Not enough cards in deck');
        }

        match ($this->drawMode) {
            DrawMode::Random => $this->drawRandomToHand($hand, $num),
            DrawMode::Sequential, DrawMode::OneByOne => $this->deck->moveTo($hand, $num),
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
            throw new \InvalidArgumentException('Dealer does not have this hand');
        }
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
