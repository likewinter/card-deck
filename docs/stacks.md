# Stacks, decks, and hands

## Stack

`Stack` is the foundation of every card collection in the framework.
It's an ordered, countable, iterable collection of `Card` objects with
an optional capacity limit.

The `Table`'s deck, pile, and hands are all `Stack`s.
Any custom collection you build (a tableau column, a meld, a crib) can
be a `Stack` too.

### Construction

```php
use Likewinter\CardDeck\Stack;

// Empty, no capacity
$stack = new Stack();

// Pre-filled, no capacity
$stack = Stack::fromString('A♣,2♦,3♥,4♠');

// Empty with capacity 5
$stack = new Stack(capacity: 5);

// Pre-filled with capacity (throws if cards exceed capacity)
$stack = Stack::fromString('A♣,2♦', capacity: 5);
```

Capacity is a hard limit — `addCards()` throws if it would be exceeded.
Pass `null` (the default) for an unbounded stack.

### Iteration and counting

`Stack` implements `IteratorAggregate` and `Countable`:

```php
foreach ($stack as $card) {
    echo $card;
}

count($stack);                // int
$stack->count();              // int — same thing
$stack->isEmpty();            // bool
$stack->isFull();             // bool (false if no capacity set)
```

### Inspecting cards

| Method | Description |
|--------|-------------|
| `peek(int $n = 1, bool $fromTop = true)` | Returns a new Stack with $n cards (without removing) |
| `peekRandom(int $n = 1)` | Returns a new Stack with $n random cards |
| `hasCards(Card ...$c)` | True if all given cards are present (duplicates ignored) |
| `hasExactCards(Card ...$c)` | True if all given cards are present with correct multiplicities |
| `hasRank(Rank $rank)` | True if any card in the stack has the given rank |
| `enoughCards(int $n)` | True if the stack has at least $n cards |
| `isSame(Stack $other)` | True if same cards in same order |

`peek` and `peekRandom` return a *new* `Stack` — they don't modify the
original. `fromTop = false` peeks from the bottom.

### Adding and removing

| Method | Description |
|--------|-------------|
| `addCards(Card ...$c)` | Appends cards (throws if capacity exceeded) |
| `removeCards(Card ...$c)` | Removes given cards (throws if not present) |
| `clear()` | Empties the stack |

`removeCards` matches via `PlayableCard::equals()`, so you can remove a
card using a different object with the same identity. For `Card`, that
means same suit and rank. For `CardInPlay`, same card and same face.
For `Wildcard`, same wild card.

### Taking and moving

| Method | Description |
|--------|-------------|
| `takeCards(int $n = 1, bool $fromTop = true)` | Removes and returns $n cards as a new Stack |
| `takeTop(int $n = 1)` | Shortcut for `takeCards($n, true)` |
| `takeBottom(int $n = 1)` | Shortcut for `takeCards($n, false)` |
| `moveTo(Stack $target, int $n = 1, bool $fromTop = true)` | Moves $n cards to another stack |
| `moveAllTo(Stack $target)` | Moves all cards to another stack |
| `moveCardsTo(Stack $target, Card ...$c)` | Moves specific cards to another stack |

**Atomicity:** `moveTo` and `moveAllTo` are atomic. If the target
rejects the cards (e.g. its capacity would be exceeded), the source
cards are rolled back before the exception propagates. Cards are never
lost to a failed move.

### Reordering

| Method | Description |
|--------|-------------|
| `sort(callable $cb)` | Sorts in place with a usort-style callback |
| `shuffle()` | Shuffles in place |

### String representation

```php
echo $stack;                  // A♣,2♦,3♥,4♠
(string) $stack;              // same
Stack::fromString('A♣,2♦');   // round-trip
```

## Decks

A "deck" is just a `Stack` with a capacity. Use
[`DeckBuilder`](deck-builder.md) to construct decks with standard
compositions (52-card, euchre, pinochle, multi-deck, etc.) — it returns
a `Stack` whose capacity equals the card count.

```php
$deck = DeckBuilder::standard52()->build();   // Stack with capacity 52
$deck = DeckBuilder::euchre()->build();       // Stack with capacity 24
echo $deck->capacity;                         // 24
```

## Rank and suit helpers

`Stack` includes helpers for rank/suit inspection and sorting:

```php
$stack->sortByRank(RankOrder::poker());       // sorts by poker ordering
$stack->sortByRank(RankOrder::blackjack());   // sorts by a custom order

$stack->getRanks();                           // list<Rank>
$stack->getSuits();                           // list<Suit>
```

`sortByRank()` requires a `RankOrder` argument.
See [Rank ordering](rank-order.md).
