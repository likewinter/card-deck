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
        $this->dealer = new Dealer(
            new PokerDeck(),
            array_map(fn () => new Hand(handSize: $this->handSize), range(0, $this->numHands - 1))
        );
    }

    public function deal(): void
    {
        $this->dealer->drawAll($this->handSize);
    }

    public function state(): string
    {
        $state = '';
        $state .= 'Number of hands: ' . count($this->dealer->getHands()) . PHP_EOL;
        $state .= 'Hand size: ' . $this->handSize . PHP_EOL;
        $state .= 'Deck size: ' . $this->dealer->getDeck()->deckSize . PHP_EOL;
        $state .= 'Deck: [' . $this->dealer->getDeck() . ']' . PHP_EOL;
        $state .= 'Pile: [' . $this->dealer->getPile() . ']' . PHP_EOL;

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
