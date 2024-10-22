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

    private Deck $discarded;

    public function __construct(
        private Deck $deck,
        private int $drawMode = self::DRAW_SEQUENTIAL,
        private array $hands = [],
    ) {
        $deck->shuffle();
        $this->discarded = new Deck();

        if (!in_array($drawMode, self::DRAW_MODES)) {
            throw new \InvalidArgumentException('Invalid draw mode');
        }

        foreach ($hands as $hand) {
            if (!is_a($hand, Hand::class)) {
                throw new \InvalidArgumentException('Hands must be instances of Hand');
            }
        }
    }

    public function addHand(Hand $hand): void
    {
        $this->hands[] = $hand;
    }

    public function removeHand(Hand $hand): void
    {
        $this->discardHand($hand);
        unset($this->hands[array_search($hand, $this->hands)]);
    }

    public function checkHand(Hand $hand): void
    {
        if (!in_array($hand, $this->hands)) {
            throw new \InvalidArgumentException('Dealer does not have this hand');
        }
    }

    public function getHands(): array
    {
        return $this->hands;
    }

    public function getDeck(): Deck
    {
        return $this->deck;
    }

    public function getDiscarded(): Deck
    {
        return $this->discarded;
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
                    $hand->add($this->deck->takeTopCard());
                }
            }
        }

        if ($this->drawMode === self::DRAW_SEQUENTIAL) {
            foreach ($this->hands as $hand) {
                $hand->addMany($this->deck->takeTopCards($num));
            }
        }

        if ($this->drawMode === self::DRAW_RANDOM) {
            for ($i = 0; $i < $num; $i++) {
                foreach ($this->hands as $hand) {
                    $hand->add($this->deck->takeRandomCard());
                }
            }
        }
    }

    public function drawToHand(Hand $hand, int $num = 1): void
    {
        $this->checkHand($hand);

        if ($num > $this->deck->count()) {
            throw new \InvalidArgumentException('Not enough cards in deck');
        }

        $hand->addMany($this->deck->takeTopCards($num));
    }

    public function discard(array $cards, Hand $hand): void
    {
        $this->checkHand($hand);
        $this->discarded->addMany($hand->removeMany($cards));
    }

    public function discardHand(Hand $hand): void
    {
        $this->checkHand($hand);
        $this->discarded->addMany($hand->clear());
    }
}
