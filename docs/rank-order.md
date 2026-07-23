# Rank ordering

## Why ranks have no intrinsic value

`Rank` is a pure enum — it has identity but no ordering. This is
deliberate: different games order ranks differently.

| Game | Ordering |
|------|----------|
| Poker | 2 < 3 < … < K < A |
| Blackjack | 2–10 at face, J/Q/K = 10, A = 1 or 11 |
| Belote | J > 9 > A > 10 > K > Q > 8 > 7 |
| Pinochle | 10 > A > K > Q > J > 9 (within each suit) |
| Skat | Jacks are the highest trump, above Ace |

If `Rank` carried an integer value (as it did in an earlier version of
this library), it would silently bake in one game's ordering and break
the others. Instead, ordering lives in `RankOrder`, which you supply.

## RankOrder

`RankOrder` is an immutable value object that maps ranks to integer
values and provides comparison methods.

```php
use Likewinter\CardDeck\RankOrder;

$order = RankOrder::poker();
$order->value(Rank::Ace);              // 14
$order->value(Rank::Two);              // 2
$order->compare(Rank::King, Rank::Queen);  // 1 (King is higher)
$order->isHigher(Rank::Ace, Rank::King);   // true
$order->isHighest(Rank::Ace);              // true
$order->next(Rank::Ten);                   // Jack
$order->previous(Rank::Ace);               // King
```

### Built-in presets

| Preset | Values | Highest |
|--------|--------|---------|
| `RankOrder::poker()` | 2=2, 3=3, …, K=13, A=14 | Ace |
| `RankOrder::pokerLowAce()` | A=1, 2=2, …, K=13 | King |
| `RankOrder::blackjack()` | 2–10 at face, J/Q/K=10, A=11 | Ace |

`pokerLowAce()` is used by `PokerHand` to detect the wheel straight
(A-2-3-4-5, where Ace counts as 1).

`blackjack()` provides hard values (Ace=11). Soft/hard hand-total logic
is the game's responsibility — construct a second `RankOrder` with
Ace=1 if you need soft totals.

### Custom orderings

Use `fromRanks()` with the ranks listed lowest-first:

```php
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\Card\Rank;

// Belote: 7 < 8 < Q < K < 10 < A < 9 < J
$belote = RankOrder::fromRanks(
    Rank::Seven, Rank::Eight, Rank::Queen, Rank::King,
    Rank::Ten, Rank::Ace, Rank::Nine, Rank::Jack,
);

$belote->isHigher(Rank::Jack, Rank::Ace);  // true
$belote->next(Rank::Ace);                  // Nine
$belote->isHighest(Rank::Jack);            // true
```

`fromRanks()` assigns positional values (1, 2, 3, …) internally.
The values are only meaningful for comparison — if your game needs
specific scoring values, that's game logic, not a RankOrder concern.

### API reference

| Method | Returns | Description |
|--------|---------|-------------|
| `value(Rank $r)` | `int` | The comparison value of $r |
| `compare(Rank $a, Rank $b)` | `int` | -1, 0, or 1 |
| `isHigher(Rank $a, Rank $b)` | `bool` | True if $a > $b |
| `isHighest(Rank $r)` | `bool` | True if $r is the highest in this order |
| `next(Rank $r)` | `?Rank` | Next-higher rank, or null |
| `previous(Rank $r)` | `?Rank` | Next-lower rank, or null |

`value()` throws `InvalidArgumentException` if the rank isn't in the
order (e.g. `Rank::Joker` in `RankOrder::poker()`).

## Using RankOrder with Stack

`Stack::sortByRank()` sorts cards by a `RankOrder`:

```php
$stack->sortByRank(RankOrder::poker());       // poker ordering
$stack->sortByRank(RankOrder::blackjack());   // custom order
```

For more control, use `Stack::sort()` with a closure:

```php
$order = RankOrder::poker();
$stack->sort(fn(Card $a, Card $b) => $order->compare($a->rank, $b->rank));
```
