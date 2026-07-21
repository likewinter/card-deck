# Trick-taking

Trick-taking games (Bridge, Spades, Hearts, Euchre, Pinochle, Skat,
Whist, Belote) share three concepts: suit ordering (with optional
trump), tricks, and turn order. The framework provides primitives for
all three.

## SuitOrder

`SuitOrder` encodes the trump configuration and answers "does card A
beat card B in this trick?" A `?Suit $trumpSuit` is all it needs —
`null` means no trump, a `Suit` means that suit is trump.

```php
use Likewinter\CardDeck\SuitOrder;

// Factories
SuitOrder::noTrump();                  // no trump
SuitOrder::suit(Suit::Spades);         // Spades are trump

// Usage
$order = SuitOrder::suit(Suit::Spades);
$order->isTrump($card);                // bool
$order->beats($a, $b, $leadSuit, $rankOrder);  // bool
```

### Construction rules

- `Suit::Joker` cannot be trump.

### `beats()` — the trick resolution rule

`SuitOrder::beats(Card $a, Card $b, ?Suit $leadSuit, RankOrder $rankOrder)`
implements the standard trick-taking comparison:

1. **Trump beats non-trump.** Any trump card beats any non-trump card.
2. **Higher trump beats lower trump.** Compared via the supplied
   `RankOrder`.
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
    suitOrder: SuitOrder::suit(Suit::Spades),
    rankOrder: RankOrder::poker(),
    numPlayers: 4,
);

// Players play in turn
$trick->play(0, $card0);   // player 0 leads (sets the lead suit)
$trick->play(1, $card1);
$trick->play(2, $card2);
$trick->play(3, $card3);

// Determine the winner
$trick->isComplete();      // true (all 4 played)
$trick->winner();          // int — the player index who won
$trick->leadSuit();        // the suit that was led

// Reset for the next trick
$trick->clear();
```

### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `play(int $player, Card $card)` | `void` | A player plays a card |
| `isComplete()` | `bool` | All players have played |
| `isEmpty()` | `bool` | No cards played yet |
| `winner()` | `int` | Player index who won (throws if incomplete) |
| `leadSuit()` | `?Suit` | The suit that was led |
| `cards()` | `list<Card>` | Cards in play order |
| `players()` | `list<int>` | Player indices in play order |
| `clear()` | `void` | Reset for reuse |

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
$suitOrder = SuitOrder::suit(Suit::Spades);
$rankOrder = RankOrder::poker();
$ring = new PlayerRing(numPlayers: 4);
$trick = new Trick($suitOrder, $rankOrder, numPlayers: 4);

for ($i = 0; $i < 4; $i++) {
    $player = $ring->current();
    $card = $hands[$player]->takeTop();   // your game's logic
    $trick->play($player, $card);
    $ring->next();
}

$winner = $trick->winner();
$trick->clear();
$ring->setCurrent($winner);  // winner leads the next trick
```
