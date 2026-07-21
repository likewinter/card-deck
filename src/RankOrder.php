<?php

namespace Likewinter\CardDeck;

use Likewinter\CardDeck\Card\Rank;

/**
 * Game-specific rank ordering and values.
 *
 * A Rank by itself has no ordering — it is just an identity. Different games
 * order ranks differently (poker: 2 < 3 < ... < A; blackjack: J/Q/K all
 * equal 10; belote: J > 9 > A > 10 > K > Q > 8 > 7). This class encapsulates
 * a game's rank ordering so generic entities stay game-agnostic.
 *
 * The "value" of a rank is an integer used for comparison; it has no
 * intrinsic meaning beyond ordering within a given RankOrder.
 */
final readonly class RankOrder
{
    /**
     * @param array<string, int> $values Map of rank name => comparison value.
     * @param Rank $highest The rank considered "highest" in this ordering.
     */
    public function __construct(
        public array $values,
        public Rank $highest,
    ) {}

    /**
     * Standard poker ordering: 2 < 3 < ... < K < A, with Ace highest.
     */
    public static function poker(): self
    {
        return new self(
            values: [
                Rank::Two->name => 2, Rank::Three->name => 3, Rank::Four->name => 4,
                Rank::Five->name => 5, Rank::Six->name => 6, Rank::Seven->name => 7,
                Rank::Eight->name => 8, Rank::Nine->name => 9, Rank::Ten->name => 10,
                Rank::Jack->name => 11, Rank::Queen->name => 12, Rank::King->name => 13,
                Rank::Ace->name => 14,
            ],
            highest: Rank::Ace,
        );
    }

    /**
     * Poker ordering where Ace is treated as 1 (for the wheel straight
     * A-2-3-4-5). Used by PokerHand for straight detection.
     */
    public static function pokerLowAce(): self
    {
        return new self(
            values: [
                Rank::Ace->name => 1,
                Rank::Two->name => 2, Rank::Three->name => 3, Rank::Four->name => 4,
                Rank::Five->name => 5, Rank::Six->name => 6, Rank::Seven->name => 7,
                Rank::Eight->name => 8, Rank::Nine->name => 9, Rank::Ten->name => 10,
                Rank::Jack->name => 11, Rank::Queen->name => 12, Rank::King->name => 13,
            ],
            highest: Rank::King,
        );
    }

    /**
     * Blackjack hard values: 2-10 at face, J/Q/K = 10, Ace = 11.
     * Soft/hand-total logic is the game's responsibility.
     */
    public static function blackjack(): self
    {
        return new self(
            values: [
                Rank::Two->name => 2, Rank::Three->name => 3, Rank::Four->name => 4,
                Rank::Five->name => 5, Rank::Six->name => 6, Rank::Seven->name => 7,
                Rank::Eight->name => 8, Rank::Nine->name => 9, Rank::Ten->name => 10,
                Rank::Jack->name => 10, Rank::Queen->name => 10, Rank::King->name => 10,
                Rank::Ace->name => 11,
            ],
            highest: Rank::Ace,
        );
    }

    public function value(Rank $rank): int
    {
        return $this->values[$rank->name]
            ?? throw new \InvalidArgumentException("Rank {$rank->name} is not in this RankOrder");
    }

    /**
     * Returns -1, 0, or 1 if $a is lower, equal, or higher than $b.
     */
    public function compare(Rank $a, Rank $b): int
    {
        return $this->value($a) <=> $this->value($b);
    }

    public function isHigher(Rank $a, Rank $b): bool
    {
        return $this->value($a) > $this->value($b);
    }

    public function isHighest(Rank $rank): bool
    {
        return $rank === $this->highest;
    }

    /**
     * Returns the next-higher rank in this ordering, or null if $rank is
     * the highest.
     */
    public function next(Rank $rank): ?Rank
    {
        $current = $this->value($rank);
        $candidates = [];
        foreach (Rank::cases() as $r) {
            if (!array_key_exists($r->name, $this->values)) {
                continue;
            }
            $v = $this->values[$r->name];
            if ($v > $current) {
                $candidates[$v] = $r;
            }
        }
        if (empty($candidates)) {
            return null;
        }
        ksort($candidates);

        return array_shift($candidates);
    }

    /**
     * Returns the next-lower rank in this ordering, or null if $rank is
     * the lowest.
     */
    public function previous(Rank $rank): ?Rank
    {
        $current = $this->value($rank);
        $candidates = [];
        foreach (Rank::cases() as $r) {
            if (!array_key_exists($r->name, $this->values)) {
                continue;
            }
            $v = $this->values[$r->name];
            if ($v < $current) {
                $candidates[$v] = $r;
            }
        }
        if (empty($candidates)) {
            return null;
        }
        krsort($candidates);

        return array_shift($candidates);
    }
}
