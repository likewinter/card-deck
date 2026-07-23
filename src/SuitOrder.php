<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\Suit;

/**
 * Game-specific suit ordering and trump rules for trick-taking games.
 *
 * Encapsulates the full question 'does card A beat card B in this trick?'
 * given a trump configuration, the suit that was led, and the rank
 * ordering. Games construct a SuitOrder with their trump rules and
 * rank ordering, then use beats() to resolve tricks.
 */
final readonly class SuitOrder
{
    public function __construct(
        public ?Suit $trumpSuit,
        private RankOrder $rankOrder,
    ) {
        if ($trumpSuit === Suit::Joker) {
            throw new \InvalidArgumentException('Joker cannot be trump');
        }
    }

    public static function noTrump(RankOrder $rankOrder): self
    {
        return new self(null, $rankOrder);
    }

    public static function suit(Suit $suit, RankOrder $rankOrder): self
    {
        return new self($suit, $rankOrder);
    }

    public function isTrump(Card $card): bool
    {
        return $this->trumpSuit !== null && $card->suit === $this->trumpSuit;
    }

    /**
     * Does $a beat $b in a trick where $leadSuit was the first card's suit?
     *
     * Rules (standard trick-taking):
     *   1. Trump beats any non-trump.
     *   2. Higher trump beats lower trump.
     *   3. Within the same suit, higher rank beats lower rank.
     *   4. A card not following the lead suit cannot win unless it's trump.
     *   5. If neither card follows lead and neither is trump, neither beats
     *      the other — returns false (the lead card remains winning by
     *      default; the caller resolves ties by keeping the current winner).
     */
    public function beats(Card $a, Card $b, ?Suit $leadSuit): bool
    {
        $aTrump = $this->isTrump($a);
        $bTrump = $this->isTrump($b);

        // Trump beats non-trump
        if ($aTrump && !$bTrump) {
            return true;
        }
        if (!$aTrump && $bTrump) {
            return false;
        }

        // Both trump: higher rank wins
        if ($aTrump) {
            return $this->rankOrder->isHigher($a->rank, $b->rank);
        }

        // Neither trump: must follow lead suit to win
        $aFollows = $leadSuit !== null && $a->suit === $leadSuit;
        $bFollows = $leadSuit !== null && $b->suit === $leadSuit;

        if ($aFollows && !$bFollows) {
            return true;
        }
        if (!$aFollows && $bFollows) {
            return false;
        }

        // Both follow lead (or neither does): higher rank wins if same suit
        if ($a->suit === $b->suit) {
            return $this->rankOrder->isHigher($a->rank, $b->rank);
        }

        // Different non-trump, non-lead suits: a cannot beat b
        return false;
    }
}
