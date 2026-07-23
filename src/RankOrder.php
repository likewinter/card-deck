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
 *
 * Construct via the static factories or fromRanks():
 *   RankOrder::poker()
 *   RankOrder::blackjack()
 *   RankOrder::fromRanks(Rank::Nine, Rank::Jack, Rank::Queen, Rank::King, Rank::Ten, Rank::Ace)
 */
final readonly class RankOrder
{
    /**
     * @param array<string, int> $values Map of rank name => comparison value.
     * @param array<string, Rank> $nextMap Map of rank name => next-higher Rank.
     * @param array<string, Rank> $prevMap Map of rank name => next-lower Rank.
     */
    private function __construct(
        private array $values,
        private Rank $highest,
        private array $nextMap,
        private array $prevMap,
    ) {}

    /**
     * Build a RankOrder from an ordered list of ranks (lowest first).
     *
     * Each rank gets a value equal to its position (1-based). Duplicate
     * values are not supported — for games where multiple ranks share a
     * value (e.g. blackjack face cards), use the dedicated factory.
     */
    public static function fromRanks(Rank ...$ordered): self
    {
        if (empty($ordered)) {
            throw new \InvalidArgumentException('RankOrder requires at least one rank');
        }

        $values = [];
        $i = 0;
        foreach ($ordered as $rank) {
            $values[$rank->name] = ++$i;
        }

        return self::build($values, $ordered[count($ordered) - 1]);
    }

    /**
     * Standard poker ordering: 2 < 3 < ... < K < A, with Ace highest.
     * Values match rank face values (Two=2, ..., Ace=14) because
     * PokerHand's wheel detection depends on them.
     */
    public static function poker(): self
    {
        return self::build(
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
        return self::build(
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
     *
     * Note: J/Q/K share the value 10, so this cannot use fromRanks()
     * (which assigns unique positional values).
     */
    public static function blackjack(): self
    {
        return self::build(
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
        $this->value($rank);

        return $this->nextMap[$rank->name] ?? null;
    }

    /**
     * Returns the next-lower rank in this ordering, or null if $rank is
     * the lowest.
     */
    public function previous(Rank $rank): ?Rank
    {
        $this->value($rank);

        return $this->prevMap[$rank->name] ?? null;
    }

    /**
     * @param array<string, int> $values
     */
    private static function build(array $values, Rank $highest): self
    {
        $rankByName = [];
        foreach (Rank::cases() as $case) {
            $rankByName[$case->name] = $case;
        }

        $byValue = [];
        foreach ($values as $name => $value) {
            $byValue[$value][] = $name;
        }
        ksort($byValue);

        $flat = [];
        foreach ($byValue as $names) {
            foreach ($names as $name) {
                $flat[] = $name;
            }
        }

        $nextMap = [];
        $prevMap = [];
        for ($i = 0, $n = count($flat); $i < $n; $i++) {
            if ($i < $n - 1) {
                $nextMap[$flat[$i]] = $rankByName[$flat[$i + 1]];
            }
            if ($i > 0) {
                $prevMap[$flat[$i]] = $rankByName[$flat[$i - 1]];
            }
        }

        return new self($values, $highest, $nextMap, $prevMap);
    }
}
