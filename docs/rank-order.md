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

Construct a `RankOrder` with any value map:

```php
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\Card\Rank;

// Belote: J=20, 9=14, A=11, 10=10, K=4, Q=3, 8=0, 7=0
$belote = new RankOrder(
    values: [
        Rank::Seven->name => 0,
        Rank::Eight->name => 0,
        Rank::Queen->name => 3,
        Rank::King->name => 4,
        Rank::Ten->name => 10,
        Rank::Ace->name => 11,
        Rank::Nine->name => 14,
        Rank::Jack->name => 20,
    ],
    highest: Rank::Jack,
);

$belote->isHigher(Rank::Jack, Rank::Ace);  // true (20 > 11)
```

The values map is keyed by `Rank::name` (the enum case name, like
`'Jack'`), not by the enum instance — PHP array keys can't be enums.

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

## Using RankOrder with Hand

`Hand::sortByRank()` accepts an optional `RankOrder`:

```php
$hand->sortByRank();                          // uses poker() by default
$hand->sortByRank(RankOrder::blackjack());    // custom order
```

## Using RankOrder with Stack

For custom sorting, use `Stack::sort()` with a closure:

```php
$order = RankOrder::belote();
$stack->sort(fn(Card $a, Card $b) => $order->compare($a->rank, $b->rank));
```
