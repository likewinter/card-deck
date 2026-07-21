<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\{Rank, Suit};

/**
 * Fluent factory for building decks of arbitrary composition.
 *
 * Most games use the standard 52-card deck, but many need variations:
 *   - Jokers added (Canasta, some poker variants)
 *   - Multiple decks shuffled together (Blackjack shoe: 6×52)
 *   - Short decks (Euchre: 9-A, Piquet: 7-A)
 *   - Custom rank ranges and suit subsets
 *   - Duplicated cards (Pinochle: two of each 9-A per suit)
 *
 * Usage:
 *   DeckBuilder::standard52()->build();
 *   DeckBuilder::standard52()->withJokers(2)->build();
 *   DeckBuilder::standard52()->times(6)->build();                 // Blackjack shoe
 *   DeckBuilder::range(Rank::Nine, Rank::Ace)->build();          // Euchre
 *   DeckBuilder::pinochle()->build();
 *   (new DeckBuilder())->suits(Suit::Hearts, Suit::Spades)->range(Rank::Seven, Rank::Ace)->build();
 */
class DeckBuilder
{
    /** @var list<Suit> */
    private array $suits = [];

    /** @var list<Rank> */
    private array $ranks = [];

    /** @var list<Card> */
    private array $extraCards = [];

    private int $copies = 1;

    private int $jokerCount = 0;

    /**
     * Start with a standard 52-card deck (4 suits × 13 ranks, no jokers).
     */
    public static function standard52(): self
    {
        return (new self())
            ->suits(Suit::Hearts, Suit::Diamonds, Suit::Clubs, Suit::Spades)
            ->allRanks();
    }

    /**
     * Start with a standard deck plus N jokers.
     */
    public static function standard52WithJokers(int $jokers = 2): self
    {
        return self::standard52()->withJokers($jokers);
    }

    /**
     * Build a Euchre deck: 9, 10, J, Q, K, A in all four suits (24 cards).
     */
    public static function euchre(): self
    {
        return (new self())
            ->suits(Suit::Hearts, Suit::Diamonds, Suit::Clubs, Suit::Spades)
            ->range(Rank::Nine, Rank::Ace);
    }

    /**
     * Build a Pinochle deck: two of each 9, J, Q, K, 10, A in all four
     * suits (48 cards). Note: Pinochle rank ordering differs from poker
     * (10 is high, not Ace) — that's a RankOrder concern, not a deck
     * composition concern.
     */
    public static function pinochle(): self
    {
        return (new self())
            ->suits(Suit::Hearts, Suit::Diamonds, Suit::Clubs, Suit::Spades)
            ->ranks(Rank::Nine, Rank::Jack, Rank::Queen, Rank::King, Rank::Ten, Rank::Ace)
            ->times(2);
    }

    /**
     * Build a Piquet deck: 7, 8, 9, 10, J, Q, K, A in all four suits
     * (32 cards).
     */
    public static function piquet(): self
    {
        return (new self())
            ->suits(Suit::Hearts, Suit::Diamonds, Suit::Clubs, Suit::Spades)
            ->range(Rank::Seven, Rank::Ace);
    }

    /**
     * Start with a rank range (inclusive) in all four standard suits.
     * Use after specifying suits, or use standard52() then range() to
     * override ranks.
     */
    public static function ranging(Rank $low, Rank $high): self
    {
        return (new self())
            ->suits(Suit::Hearts, Suit::Diamonds, Suit::Clubs, Suit::Spades)
            ->range($low, $high);
    }

    public function suits(Suit ...$suits): self
    {
        $this->suits = array_values($suits);
        return $this;
    }

    public function allRanks(): self
    {
        $this->ranks = Rank::casesWithoutJoker();
        return $this;
    }

    public function ranks(Rank ...$ranks): self
    {
        $this->ranks = array_values($ranks);
        return $this;
    }

    /**
     * Set ranks to a consecutive range (inclusive), using poker ordering
     * (2 < 3 < ... < A) to determine what's between $low and $high.
     */
    public function range(Rank $low, Rank $high): self
    {
        $order = RankOrder::poker();
        $ranks = [];
        $current = $low;
        while ($current !== null) {
            $ranks[] = $current;
            if ($current === $high) {
                break;
            }
            $current = $order->next($current);
        }
        if (end($ranks) !== $high) {
            throw new \InvalidArgumentException(
                "Rank range {$low->name}..{$high->name} is invalid"
            );
        }
        $this->ranks = $ranks;
        return $this;
    }

    /**
     * Add N jokers to the deck.
     */
    public function withJokers(int $count = 2): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Joker count must be non-negative');
        }
        $this->jokerCount = $count;
        return $this;
    }

    /**
     * Repeat the entire deck N times (for multi-deck shoes like Blackjack).
     * Jokers are not multiplied — they're added after the multiplied base.
     */
    public function times(int $copies): self
    {
        if ($copies < 1) {
            throw new \InvalidArgumentException('Copies must be at least 1');
        }
        $this->copies = $copies;
        return $this;
    }

    /**
     * Add specific extra cards beyond the standard composition.
     * Useful for custom decks or promotions.
     */
    public function addExtra(Card ...$cards): self
    {
        foreach ($cards as $card) {
            $this->extraCards[] = $card;
        }
        return $this;
    }

    /**
     * Build and return the Deck.
     *
     * @return Deck
     */
    public function build(): Deck
    {
        $cards = [];

        for ($copy = 0; $copy < $this->copies; $copy++) {
            foreach ($this->suits as $suit) {
                if ($suit === Suit::Joker) {
                    continue;
                }
                foreach ($this->ranks as $rank) {
                    if ($rank === Rank::Joker) {
                        continue;
                    }
                    $cards[] = new Card(suit: $suit, rank: $rank);
                }
            }
        }

        // Add jokers (not multiplied)
        for ($i = 0; $i < $this->jokerCount; $i++) {
            $cards[] = new Card(suit: Suit::Joker, rank: Rank::Joker);
        }

        // Add extra cards
        foreach ($this->extraCards as $card) {
            $cards[] = $card;
        }

        $total = count($cards);
        return new Deck($cards, $total);
    }

    /**
     * Build and return the deck as a list of Card objects (no Deck wrapper).
     *
     * @return list<Card>
     */
    public function buildCards(): array
    {
        return [...$this->build()];
    }
}
