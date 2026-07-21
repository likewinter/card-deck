# The dealer

`Dealer` orchestrates dealing cards from a `Stack` (the deck) to
`Hand`s, managing a discard pile, and resetting the game for a new
round.

## Construction

```php
use Likewinter\CardDeck\Dealer;
use Likewinter\CardDeck\DeckBuilder;

$dealer = new Dealer(deck: DeckBuilder::standard52()->build());

// With options:
$dealer = new Dealer(
    deck: DeckBuilder::standard52()->build(),
    hands: [$alice, $bob],              // pre-register hands
    drawMode: Dealer::DRAW_ONE_BY_ONE,
    shuffle: true,                       // shuffle the deck on construction
);
```

### Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `deck` | (required) | The `Stack` to deal from (typically from `DeckBuilder`) |
| `hands` | `[]` | Initial list of `Hand` objects |
| `drawMode` | `DRAW_SEQUENTIAL` | How cards are distributed |
| `shuffle` | `false` | Whether to shuffle the deck on construction |

**Shuffle is opt-in.** The dealer does not shuffle by default — this
enables deterministic deals for testing and fixed scenarios. Pass
`shuffle: true` or call `$dealer->getDeck()->shuffle()` explicitly.

## Managing hands

```php
// Add hands
$dealer->addHands($alice, $bob, $carol);

// List hands
$dealer->getHands();              // list<Hand>

// Remove a hand (its cards go to the pile)
$dealer->removeHands($bob);

// Get the deck and pile
$dealer->getDeck();               // Stack
$dealer->getPile();               // Stack (the discard pile)
```

`removeHands` uses identity comparison (`!==`) to find the hand, so two
empty hands with the same string representation are not confused.

## Draw modes

Three modes control how `drawAll()` distributes cards:

| Mode | Behavior |
|------|----------|
| `DRAW_SEQUENTIAL` | Deals $n cards to hand 1, then $n to hand 2, … |
| `DRAW_ONE_BY_ONE` | Deals 1 card to hand 1, 1 to hand 2, …, repeat $n times |
| `DRAW_RANDOM` | Like `ONE_BY_ONE` but each card is drawn randomly from the deck |

### Dealing to all hands

```php
// Deal 5 cards to each hand
$dealer->drawAll(5);

// Throws if:
// - no hands are registered
// - not enough cards in the deck (n × numHands > deck count)
```

### Dealing to a specific hand

```php
// Deal 2 cards to $alice only
$dealer->drawToHand($alice, 2);

// Throws if:
// - the hand isn't registered with this dealer
// - not enough cards in the deck
```

`drawToHand` honors the draw mode: with `DRAW_RANDOM`, cards are drawn
randomly; otherwise they come off the top.

## Discarding

```php
// Discard the entire hand to the pile
$dealer->discard($alice);

// Discard specific cards
$dealer->discard($alice, $card1, $card2);
```

`discard` moves cards from the hand to the dealer's pile. With no card
arguments, the whole hand is discarded.

## Resetting

```php
$dealer->resetGame();
```

Returns all cards from hands and the pile to the deck. **Does not
shuffle** — call `$dealer->getDeck()->shuffle()` if you want a reshuffle
between rounds.

## Error handling

All dealer errors throw `DealerException` (extends `\Exception`):

| Scenario | Exception |
|----------|-----------|
| No hands registered for `drawAll` | `DealerException: No hands provided` |
| Not enough cards for `drawAll` | `DealerException: Not enough cards in deck` |
| Not enough cards for `drawToHand` | `DealerException: Not enough cards in deck` |
| Hand not registered with dealer | `DealerException: Dealer does not have this hand` |
| Invalid draw mode | `DealerException: Invalid draw mode` |
| Non-Hand in constructor hands array | `DealerException: Hands must be instances of Hand` |
