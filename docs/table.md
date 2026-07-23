# The table

`Table` owns the deck, named hands, and discard pile for a game session.
It is the single orchestration point for card movement — games register
named hands, draw cards into them, discard back to the pile, and reset
for a new round.

> **Scope:** `Table` is designed for dealer-pattern games — deck → named
> hands → pile lifecycle (Poker, Blackjack, Spades). Solitaire-style
> games with non-standard pile topologies (tableau columns, foundations,
> a stock/waste pair) should manage `Stack`s directly rather than
> forcing them into the Table model.

## Construction

```php
use Likewinter\CardDeck\Table;
use Likewinter\CardDeck\DeckBuilder;

$table = new Table(deck: DeckBuilder::standard52()->build());

// With options:
$table = new Table(
    deck: DeckBuilder::standard52()->build(),
    drawMode: DrawMode::OneByOne,
    shuffle: true,
);
```

### Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `deck` | (required) | The `Stack` to deal from (typically from `DeckBuilder`) |
| `drawMode` | `DrawMode::Sequential` | How cards are distributed by `drawAll()` |
| `shuffle` | `false` | Whether to shuffle the deck on construction |

**Shuffle is opt-in.** The table does not shuffle by default — this
enables deterministic deals for testing and fixed scenarios. Pass
`shuffle: true` or call `$table->shuffle()` explicitly.

## Managing hands

Hands are keyed by string identifiers chosen by the game:

```php
// Add hands
$table->addHand('dealer', new Stack());
$table->addHand('north', new Stack(capacity: 13));
$table->addHand('south', new Stack(capacity: 13));

// Retrieve a hand
$table->hand('north');            // Stack

// Check existence
$table->hasHand('north');         // true

// List hand names (insertion order)
$table->handNames();              // ['dealer', 'north', 'south']

// Remove a hand (its cards go to the pile)
$table->removeHand('south');
```

## Draw modes

Three modes control how `drawAll()` distributes cards:

| Mode | Behavior |
|------|----------|
| `DrawMode::Sequential` | Deals $n cards to hand 1, then $n to hand 2, … |
| `DrawMode::OneByOne` | Deals 1 card to hand 1, 1 to hand 2, …, repeat $n times |
| `DrawMode::Random` | Like `OneByOne` but each card is drawn randomly from the deck |

### Drawing to all hands

```php
// Deal 5 cards to each registered hand
$table->drawAll(5);

// Throws if:
// - no hands are registered
// - not enough cards in the deck (n × numHands > deck count)
```

### Drawing to a specific hand

```php
// Deal 2 cards to 'north' only
$table->draw('north', 2);

// Throws if:
// - the hand doesn't exist
// - not enough cards in the deck
```

`draw` honors the draw mode: with `DrawMode::Random`, cards are drawn
randomly; otherwise they come off the top.

## Discarding

```php
// Discard the entire hand to the pile
$table->discard('north');

// Discard specific cards
$table->discard('north', $card1, $card2);

// Collect external cards into the pile (e.g., played trick cards)
$table->collectToPile(...$trick->cards());
```

`discard` moves cards from a named hand to the pile. With no card
arguments, the whole hand is discarded. `collectToPile` accepts cards
from outside the table (e.g., cards played to a `Trick`) so that
`reset()` can recover them.

## Inspection

```php
$table->deckCount();              // int — cards remaining in the deck
$table->pileCount();              // int — cards in the discard pile
```

The underlying `Stack` objects are private. All mutation goes through
Table's methods.

## Resetting

```php
$table->reset();
```

Returns all cards from hands and the pile to the deck. **Does not
shuffle** — call `$table->shuffle()` if you want a reshuffle between
rounds.

## Error handling

| Scenario | Exception |
|----------|-----------|
| No hands registered for `drawAll` | `\LogicException: No hands registered` |
| Not enough cards for `drawAll` | `\LogicException: Not enough cards in deck` |
| Not enough cards for `draw` | `\LogicException: Not enough cards in deck` |
| Hand name not found | `\InvalidArgumentException: Hand '…' does not exist` |
| Duplicate hand name | `\InvalidArgumentException: Hand '…' already exists` |
