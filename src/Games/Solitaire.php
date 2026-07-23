<?php

namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\{CardInPlay, DeckBuilder, Face, RankOrder, Stack};
use Likewinter\CardDeck\Card\{Rank, Suit};

/**
 * Klondike Solitaire — a single-player game with face-down tableau cards.
 *
 * Demonstrates: CardInPlay for face-up/face-down state, Stack as
 * tableau/stock/waste/foundation piles, and face-state-dependent
 * game rules (only face-up cards can be moved).
 *
 * Simplified: single-card moves only (no multi-card sequences).
 */
readonly class Solitaire
{
    private Stack $stock;
    private Stack $waste;
    /** @var list<Stack> */
    private array $tableau;
    /** @var array<string, Stack> */
    private array $foundations;
    private RankOrder $rankOrder;

    public function __construct(?Stack $deck = null, bool $shuffle = true)
    {
        $cards = [...($deck ?? DeckBuilder::standard52()->build())];
        if ($shuffle) {
            shuffle($cards);
        }

        $this->rankOrder = RankOrder::poker();

        $offset = 0;
        $tableau = [];
        for ($i = 0; $i < 7; $i++) {
            $count = $i + 1;
            $slice = array_reverse(array_slice($cards, $offset, $count));
            $offset += $count;
            $pile = [];
            foreach ($slice as $j => $card) {
                $pile[] = new CardInPlay(
                    $card->underlyingCard(),
                    $j === 0 ? Face::Up : Face::Down,
                );
            }
            $tableau[$i] = new Stack($pile);
        }
        $this->tableau = $tableau;

        $stockCards = array_map(
            fn($c) => CardInPlay::down($c->underlyingCard()),
            array_slice($cards, $offset),
        );
        $this->stock = new Stack($stockCards);
        $this->waste = new Stack();

        $foundations = [];
        foreach (Suit::casesWithoutJoker() as $suit) {
            $foundations[$suit->value] = new Stack();
        }
        $this->foundations = $foundations;
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function stock(): Stack
    {
        return $this->stock;
    }

    public function waste(): Stack
    {
        return $this->waste;
    }

    public function tableau(int $index): Stack
    {
        return $this->tableau[$index];
    }

    public function foundation(Suit $suit): Stack
    {
        return $this->foundations[$suit->value];
    }

    // ── Drawing ────────────────────────────────────────────────────

    /**
     * Draw the top stock card to the waste (face-up).
     * If the stock is empty, recycle the waste back to stock (face-down).
     */
    public function drawFromStock(): void
    {
        if ($this->stock->isEmpty()) {
            if ($this->waste->isEmpty()) {
                throw new \LogicException('Stock and waste are both empty');
            }
            $cards = array_reverse([...$this->waste]);
            $this->waste->clear();
            foreach ($cards as $card) {
                $this->stock->addCards(CardInPlay::down($card->underlyingCard()));
            }
            return;
        }

        $card = [...$this->stock->takeTop()][0];
        $this->waste->addCards($card->reveal());
    }

    // ── Moves ──────────────────────────────────────────────────────

    public function moveWasteToFoundation(Suit $suit): void
    {
        if ($this->waste->isEmpty()) {
            throw new \LogicException('Waste is empty');
        }

        $card = [...$this->waste->peek()][0];
        $this->validateFoundationMove($card, $suit);
        $this->waste->takeTop();
        $this->foundations[$suit->value]->addCards($card);
    }

    public function moveToFoundation(int $tableauIndex, Suit $suit): void
    {
        $pile = $this->tableau[$tableauIndex];
        if ($pile->isEmpty()) {
            throw new \LogicException('Tableau pile is empty');
        }

        $card = $this->tableauTop($tableauIndex);
        $this->validateFoundationMove($card, $suit);
        $pile->takeTop();
        $this->flipIfNeeded($tableauIndex);
        $this->foundations[$suit->value]->addCards($card);
    }

    public function moveWasteToTableau(int $pileIndex): void
    {
        if ($this->waste->isEmpty()) {
            throw new \LogicException('Waste is empty');
        }

        $card = [...$this->waste->peek()][0];
        $this->validateTableauMove($card, $pileIndex);
        $this->waste->takeTop();
        $this->tableau[$pileIndex]->addCards($card);
    }

    public function moveToTableau(int $fromIndex, int $toIndex): void
    {
        $from = $this->tableau[$fromIndex];
        if ($from->isEmpty()) {
            throw new \LogicException('Source tableau pile is empty');
        }

        $card = $this->tableauTop($fromIndex);
        $this->validateTableauMove($card, $toIndex);
        $from->takeTop();
        $this->flipIfNeeded($fromIndex);
        $this->tableau[$toIndex]->addCards($card);
    }

    // ── State ──────────────────────────────────────────────────────

    public function isWon(): bool
    {
        foreach ($this->foundations as $foundation) {
            if ($foundation->count() !== 13) {
                return false;
            }
        }

        return true;
    }

    // ── Private ────────────────────────────────────────────────────

    private function tableauTop(int $index): CardInPlay
    {
        $top = [...$this->tableau[$index]->peek()][0];

        if ($top->isFaceDown()) {
            throw new \LogicException('Cannot move a face-down card');
        }

        return $top;
    }

    private function flipIfNeeded(int $index): void
    {
        $pile = $this->tableau[$index];
        if ($pile->isEmpty()) {
            return;
        }

        $top = [...$pile->peek()][0];
        if ($top->isFaceDown()) {
            $pile->takeTop();
            $pile->addCards($top->reveal());
        }
    }

    private function validateFoundationMove(CardInPlay $card, Suit $suit): void
    {
        $underlying = $card->underlyingCard();

        if ($underlying->suit !== $suit) {
            throw new \InvalidArgumentException('Card suit does not match foundation');
        }

        $foundation = $this->foundations[$suit->value];

        if ($foundation->isEmpty()) {
            if ($underlying->rank !== Rank::Ace) {
                throw new \InvalidArgumentException('Only Aces can start a foundation');
            }
            return;
        }

        $topUnderlying = [...$foundation->peek()][0]->underlyingCard();
        $expected = $this->rankOrder->next($topUnderlying->rank);

        if ($underlying->rank !== $expected) {
            throw new \InvalidArgumentException('Card must be the next rank in the foundation');
        }
    }

    private function validateTableauMove(CardInPlay $card, int $pileIndex): void
    {
        $pile = $this->tableau[$pileIndex];
        $underlying = $card->underlyingCard();

        if ($pile->isEmpty()) {
            if ($underlying->rank !== Rank::King) {
                throw new \InvalidArgumentException('Only Kings can be placed on an empty tableau');
            }
            return;
        }

        $top = [...$pile->peek()][0];

        if ($top->isFaceDown()) {
            throw new \LogicException('Cannot place on a face-down card');
        }

        $topUnderlying = $top->underlyingCard();

        if ($this->rankOrder->value($underlying->rank) !== $this->rankOrder->value($topUnderlying->rank) - 1) {
            throw new \InvalidArgumentException('Card must be one rank lower');
        }

        if ($underlying->suit->getColor() === $topUnderlying->suit->getColor()) {
            throw new \InvalidArgumentException('Cards must alternate colors');
        }
    }
}
