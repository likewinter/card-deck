<?php

declare(strict_types=1);

namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\Card\Suit;
use Likewinter\CardDeck\CardInPlay;
use Likewinter\CardDeck\DeckBuilder;
use Likewinter\CardDeck\Face;
use Likewinter\CardDeck\PlayableCard;
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\Stack;

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

    /**
     * Deal the Klondike layout: 7 tableau columns of 1–7 cards (only
     * the top card face-up), the remainder to the stock, and 4 empty
     * foundations.
     *
     * @param Stack|null $deck Deck to deal from; defaults to a standard 52-card deck.
     * @param bool $shuffle Shuffle the deck before dealing.
     */
    public function __construct(?Stack $deck = null, bool $shuffle = true)
    {
        $cards = [...($deck ?? DeckBuilder::standard52()->build())];
        if ($shuffle) {
            shuffle($cards);
        }

        $this->rankOrder = RankOrder::pokerLowAce();

        $offset = 0;
        $tableau = [];
        for ($i = 0; $i < 7; $i++) {
            $count = $i + 1;
            $slice = array_slice($cards, $offset, $count);
            $offset += $count;
            $pile = [];
            foreach ($slice as $j => $card) {
                $pile[] = new CardInPlay($card->underlyingCard(), $j === ($count - 1) ? Face::Up : Face::Down);
            }
            $tableau[] = new Stack($pile);
        }
        $this->tableau = $tableau;

        $stockCards = array_map(static fn($c) => CardInPlay::down($c->underlyingCard()), array_slice($cards, $offset));
        $this->stock = new Stack($stockCards);
        $this->waste = new Stack();

        $foundations = [];
        foreach (Suit::casesWithoutJoker() as $suit) {
            $foundations[$suit->value] = new Stack();
        }
        $this->foundations = $foundations;
    }

    // ── Accessors ──────────────────────────────────────────────────

    /**
     * The face-down stock pile.
     */
    public function stock(): Stack
    {
        return $this->stock;
    }

    /**
     * The face-up waste pile drawn from the stock.
     */
    public function waste(): Stack
    {
        return $this->waste;
    }

    /**
     * A tableau column by index (0–6).
     */
    public function tableau(int $index): Stack
    {
        return $this->tableau[$index];
    }

    /**
     * The foundation pile for a suit.
     *
     * @throws \InvalidArgumentException If there is no foundation for the suit (e.g. Joker).
     */
    public function foundation(Suit $suit): Stack
    {
        return (
            $this->foundations[$suit->value] ?? throw new \InvalidArgumentException(
                "No foundation for suit '{$suit->value}'",
            )
        );
    }

    // ── Drawing ────────────────────────────────────────────────────

    /**
     * Draw the top stock card to the waste (face-up).
     * If the stock is empty, recycle the waste back to stock (face-down).
     *
     * @throws \LogicException If both stock and waste are empty.
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

        $card = $this->asCardInPlay([...$this->stock->takeTop()][0]);
        $this->waste->addCards($card->reveal());
    }

    // ── Moves ──────────────────────────────────────────────────────

    /**
     * Move the top waste card onto a foundation.
     *
     * @throws \LogicException If the waste is empty.
     * @throws \InvalidArgumentException If the move violates foundation rules (suit, rank sequence).
     */
    public function moveWasteToFoundation(Suit $suit): void
    {
        if ($this->waste->isEmpty()) {
            throw new \LogicException('Waste is empty');
        }

        $card = $this->asCardInPlay([...$this->waste->peekBottom()][0]);
        $this->validateFoundationMove($card, $suit);
        (void) $this->waste->takeBottom();
        $this->foundation($suit)->addCards($card);
    }

    /**
     * Move the top card of a tableau column onto a foundation. Flips the
     * newly exposed tableau card face-up if needed.
     *
     * @throws \LogicException If the tableau pile is empty or its top card is face-down.
     * @throws \InvalidArgumentException If the move violates foundation rules (suit, rank sequence).
     */
    public function moveToFoundation(int $tableauIndex, Suit $suit): void
    {
        $pile = $this->tableau[$tableauIndex];
        if ($pile->isEmpty()) {
            throw new \LogicException('Tableau pile is empty');
        }

        $card = $this->tableauTop($tableauIndex);
        $this->validateFoundationMove($card, $suit);
        (void) $pile->takeBottom();
        $this->flipIfNeeded($tableauIndex);
        $this->foundation($suit)->addCards($card);
    }

    /**
     * Move the top waste card onto a tableau column.
     *
     * @throws \LogicException If the waste is empty.
     * @throws \InvalidArgumentException If the move violates tableau rules (rank sequence, alternating colors).
     */
    public function moveWasteToTableau(int $pileIndex): void
    {
        if ($this->waste->isEmpty()) {
            throw new \LogicException('Waste is empty');
        }

        $card = $this->asCardInPlay([...$this->waste->peekBottom()][0]);
        $this->validateTableauMove($card, $pileIndex);
        (void) $this->waste->takeBottom();
        $this->tableau[$pileIndex]->addCards($card);
    }

    /**
     * Move the top card of one tableau column onto another. Flips the
     * newly exposed card in the source column face-up if needed.
     *
     * @throws \LogicException If the source pile is empty or its top card is face-down.
     * @throws \InvalidArgumentException If the move violates tableau rules (rank sequence, alternating colors).
     */
    public function moveToTableau(int $fromIndex, int $toIndex): void
    {
        $from = $this->tableau[$fromIndex];
        if ($from->isEmpty()) {
            throw new \LogicException('Source tableau pile is empty');
        }

        $card = $this->tableauTop($fromIndex);
        $this->validateTableauMove($card, $toIndex);
        (void) $from->takeBottom();
        $this->flipIfNeeded($fromIndex);
        $this->tableau[$toIndex]->addCards($card);
    }

    // ── State ──────────────────────────────────────────────────────

    /**
     * Whether all four foundations are complete (13 cards each).
     */
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

    private function asCardInPlay(PlayableCard $card): CardInPlay
    {
        if (!$card instanceof CardInPlay) {
            throw new \LogicException('Expected a CardInPlay');
        }

        return $card;
    }

    private function tableauTop(int $index): CardInPlay
    {
        $top = $this->asCardInPlay([...$this->tableau[$index]->peekBottom()][0]);

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

        $top = $this->asCardInPlay([...$pile->peekBottom()][0]);
        if ($top->isFaceDown()) {
            (void) $pile->takeBottom();
            $pile->addCards($top->reveal());
        }
    }

    private function validateFoundationMove(CardInPlay $card, Suit $suit): void
    {
        $underlying = $card->underlyingCard();

        if ($underlying->suit !== $suit) {
            throw new \InvalidArgumentException('Card suit does not match foundation');
        }

        $foundation = $this->foundation($suit);

        if ($foundation->isEmpty()) {
            if ($underlying->rank !== Rank::Ace) {
                throw new \InvalidArgumentException('Only Aces can start a foundation');
            }
            return;
        }

        $topUnderlying = [...$foundation->peekBottom()][0]->underlyingCard();
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

        $top = $this->asCardInPlay([...$pile->peekBottom()][0]);

        if ($top->isFaceDown()) {
            throw new \LogicException('Cannot place on a face-down card');
        }

        $topUnderlying = $top->underlyingCard();

        if ($this->rankOrder->value($underlying->rank) !== ($this->rankOrder->value($topUnderlying->rank) - 1)) {
            throw new \InvalidArgumentException('Card must be one rank lower');
        }

        if ($underlying->suit->getColor() === $topUnderlying->suit->getColor()) {
            throw new \InvalidArgumentException('Cards must alternate colors');
        }
    }
}
