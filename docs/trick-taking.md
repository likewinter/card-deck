# Trick-taking

Trick-taking games (Bridge, Spades, Hearts, Euchre, Pinochle, Skat,
Whist, Belote) share three concepts: suit ordering (with optional
trump), tricks, and turn order. The framework provides primitives for
all three.

## SuitOrder

`SuitOrder` encodes the trump configuration and answers "does card A
beat card B in this trick?" It takes a `?Suit $trumpSuit` (`null` means
no trump, a `Suit` means that suit is trump) and a `RankOrder` for
rank comparisons.

```php
use Likewinter\CardDeck\SuitOrder;
use Likewinter\CardDeck\RankOrder;

// Factories
SuitOrder::noTrump(RankOrder::poker());                  // no trump
SuitOrder::suit(Suit::Spades, RankOrder::poker());       // Spades are trump

// Usage
$order = SuitOrder::suit(Suit::Spades, RankOrder::poker());
$order->isTrump($card);                // bool
$order->beats($a, $b, $leadSuit);      // bool
```

### Construction rules

- `Suit::Joker` cannot be trump.

### `beats()` — the trick resolution rule

`SuitOrder::beats(Card $a, Card $b, ?Suit $leadSuit)`
implements the standard trick-taking comparison:

1. **Trump beats non-trump.** Any trump card beats any non-trump card.
2. **Higher trump beats lower trump.** Compared via the `RankOrder`
   supplied at construction.
3. **Led-suit follower beats off-suit.** A card following the lead suit
   beats a card in a different non-trump suit.
4. **Higher rank wins within the same suit.** Compared via `RankOrder`.
5. **Off-suit non-trump cannot beat led-suit.** Returns false — the
   caller keeps the current winner.

The `$leadSuit` is the suit of the first card played in the trick
(usually `null` when comparing against the lead card itself).

## Trick

`Trick` records the cards played by each player in one round and
determines the winner.

```php
use Likewinter\CardDeck\Trick;

$trick = new Trick(
    suitOrder: SuitOrder::suit(Suit::Spades, RankOrder::poker()),
    numPlayers: 4,
);

// Players play in turn (turn order enforced internally)
$trick->play($card0);   // player 0 leads (sets the lead suit)
$trick->play($card1);
$trick->play($card2);
$trick->play($card3);

// Determine the winner
$trick->isComplete();      // true (all 4 played)
$trick->winner();          // int — the player index who won
$trick->leadSuit();        // the suit that was led
```

### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `play(Card $card)` | `void` | Current player plays a card (turn order enforced) |
| `isComplete()` | `bool` | All players have played |
| `isEmpty()` | `bool` | No cards played yet |
| `winner()` | `int` | Player index who won (throws if incomplete) |
| `leadSuit()` | `?Suit` | The suit that was led |
| `cards()` | `list<Card>` | Cards in play order |

## PlayerRing

`PlayerRing` tracks whose turn it is, rotating through a fixed list of
players.

```php
use Likewinter\CardDeck\PlayerRing;

$ring = new PlayerRing(numPlayers: 4, startingPlayer: 0);

$ring->current();          // 0
$ring->next();             // 1
$ring->next();             // 2
$ring->peekNext();         // 3 (doesn't advance)
$ring->next();             // 3
$ring->next();             // 0 (wraps around)

$ring->reset();            // back to startingPlayer
$ring->setCurrent(2);      // jump to player 2
$ring->advance(3);         // jump 3 steps (wraps)
```

### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `current()` | `int` | Current player index |
| `next()` | `int` | Advance and return the new current |
| `peekNext()` | `int` | Next index without advancing |
| `reset()` | `void` | Reset to starting player |
| `setCurrent(int $p)` | `void` | Jump to a specific player |
| `advance(int $n)` | `int` | Advance $n steps |

## Putting it together

A minimal trick-taking round:

```php
$suitOrder = SuitOrder::suit(Suit::Spades, RankOrder::poker());
$trick = new Trick($suitOrder, numPlayers: 4);

for ($i = 0; $i < 4; $i++) {
    $card = $hands[$i]->takeTop();   // your game's logic
    $trick->play($card);
}

$winner = $trick->winner();

// Create a new Trick for the next round, with the winner leading
$trick = new Trick($suitOrder, numPlayers: 4, startingPlayer: $winner);
```
