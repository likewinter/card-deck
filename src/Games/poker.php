<?php

namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\{Dealer, Hand};
use Likewinter\CardDeck\Games\Poker\PokerDeck;
use Likewinter\CardDeck\Games\Poker\PokerHand;

class Poker
{
    private Dealer $dealer;

    public function __construct(
        private int $handSize = 5,
        private int $numHands = 4,
    ) {
        $this->dealer = new Dealer(deck: new PokerDeck());
        for ($i = 0; $i < $this->numHands; $i++) {
            $this->dealer->addHands(new Hand(handSize: $this->handSize));
        }
    }

    public function deal(): void
    {
        $this->dealer->drawAll($this->handSize);
    }

    public function gameState(): string
    {
        $state = <<<STATE
        Number of hands: {$this->numHands}
        Hand size: {$this->handSize}
        Deck size: {$this->dealer->getDeck()->deckSize}
        Deck: [{$this->dealer->getDeck()}]
        Pile: [{$this->dealer->getPile()}]

        STATE;

        return $state;
    }

    public function handsState(): string
    {
        if ($this->handSize !== PokerHand::HAND_SIZE) {
            throw new \LogicException('Hand size must be ' . PokerHand::HAND_SIZE);
        }

        $state = 'Hands:' . PHP_EOL;

        foreach ($this->dealer->getHands() as $hand) {
            $pokerHand = PokerHand::fromHand($hand);
            $state .= '  ' . $pokerHand . ' -> ' . $pokerHand->handRank->getName() . PHP_EOL;
        }

        return $state;
    }
}
