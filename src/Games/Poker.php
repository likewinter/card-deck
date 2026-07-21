<?php

namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\{Dealer, DeckBuilder, Hand};
use Likewinter\CardDeck\Games\Poker\PokerHand;

readonly class Poker
{
    private const DEFAULT_HAND_SIZE = 5;
    private const DEFAULT_NUM_HANDS = 3;
    private const MIN_HANDS = 2;
    private const MAX_HANDS = 5;

    private readonly Dealer $dealer;

    public function __construct(
        private readonly int $handSize = self::DEFAULT_HAND_SIZE,
        private readonly int $numHands = self::DEFAULT_NUM_HANDS,
        ?Dealer $dealer = null,
    ) {
        $this->dealer = $dealer ?? new Dealer(
            deck: DeckBuilder::standard52()->build(),
            shuffle: true,
        );

        $this->validateConfig();

        for ($i = 0; $i < $this->numHands; $i++) {
            $this->dealer->addHands(new Hand(capacity: $this->handSize));
        }
    }

    private function validateConfig(): void
    {
        if ($this->numHands < self::MIN_HANDS || $this->numHands > self::MAX_HANDS) {
            throw new \InvalidArgumentException(
                sprintf('Number of hands must be between %d and %d', self::MIN_HANDS, self::MAX_HANDS)
            );
        }

        if ($this->handSize !== PokerHand::HAND_SIZE) {
            throw new \InvalidArgumentException(
                sprintf('Hand size must be %d', PokerHand::HAND_SIZE)
            );
        }
    }

    public function deal(): void
    {
        $this->dealer->drawAll($this->handSize);
    }

    /**
     * Returns the current hands as PokerHand objects, in player order.
     * Hands that have not been dealt yet are returned as empty PokerHands
     * (their handRank is not meaningful until cards are dealt).
     *
     * @return list<PokerHand>
     */
    public function hands(): array
    {
        return array_map(
            fn (Hand $hand) => PokerHand::fromHand($hand),
            $this->dealer->getHands()
        );
    }

    /**
     * Resets the game for a new round: returns all cards from hands and
     * the pile to the deck and reshuffles. Hands become empty.
     */
    public function reset(): void
    {
        $this->dealer->resetGame();
        $this->dealer->getDeck()->shuffle();
    }

    public function gameState(): string
    {
        $state = <<<STATE
        Number of hands: {$this->numHands}
        Hand size: {$this->handSize}
        Deck size: {$this->dealer->getDeck()->capacity}
        Deck: [{$this->dealer->getDeck()}]
        Pile: [{$this->dealer->getPile()}]

        STATE;

        return $state;
    }

    public function handsState(): string
    {
        $state = 'Hands:' . PHP_EOL;

        foreach ($this->dealer->getHands() as $hand) {
            $pokerHand = PokerHand::fromHand($hand);
            $state .= '  ' . $pokerHand . ' -> ' . $pokerHand->handRank->getName() . PHP_EOL;
        }

        return $state;
    }

    /**
     * Returns the winning PokerHand(s) from the currently dealt hands.
     * Empty hands (no cards dealt yet) are skipped. If multiple dealt
     * hands tie for the best rank, all winners are returned.
     *
     * @return list<PokerHand>
     */
    public function winners(): array
    {
        $pokerHands = [];
        foreach ($this->dealer->getHands() as $hand) {
            if ($hand->count() !== PokerHand::HAND_SIZE) {
                continue;
            }
            $pokerHands[] = PokerHand::fromHand($hand);
        }

        if (empty($pokerHands)) {
            return [];
        }

        $best = $pokerHands[0];
        $winners = [$best];

        for ($i = 1, $n = count($pokerHands); $i < $n; $i++) {
            $cmp = $pokerHands[$i]->compare($best);
            if ($cmp > 0) {
                $best = $pokerHands[$i];
                $winners = [$best];
            } elseif ($cmp === 0) {
                $winners[] = $pokerHands[$i];
            }
        }

        return $winners;
    }

    public function winnersState(): string
    {
        $winners = $this->winners();

        if (empty($winners)) {
            return 'No hands dealt.' . PHP_EOL;
        }

        if (count($winners) === 1) {
            return 'Winner: ' . $winners[0] . ' (' . $winners[0]->handRank->getName() . ')' . PHP_EOL;
        }

        $state = 'Tie between:' . PHP_EOL;
        foreach ($winners as $winner) {
            $state .= '  ' . $winner . ' (' . $winner->handRank->getName() . ')' . PHP_EOL;
        }

        return $state;
    }
}
