# Implementing a game

This walk-through shows how to build a card game on top of the
framework, using the reference [Poker implementation](../src/Games/Poker/)
as the example. The same pattern applies to any game.

## The pattern

1. **Identify your game's needs.** What deck? How many cards per hand?
   How are ranks ordered? Is there trump? Are there wildcards?
2. **Compose the primitives.** Use `DeckBuilder` for the deck, `Stack`
   for player hands, `Table` for dealing, `RankOrder` for rank values.
3. **Add game-specific classes.** A value object for evaluated hands,
   a `HandRank` enum or similar, a `Game` orchestration class.
4. **Wire it up.** The `Game` class constructs the table, manages
   rounds, and exposes state.

## Example: Poker

The Poker implementation lives in `src/Games/Poker/`:

```
src/Games/Poker/
├── HandRank.php      — enum of 10 poker hand ranks (pure value)
└── PokerHand.php     — 5-card hand with classification and comparison
src/Games/Poker.php   — game orchestration (deal, winners, multi-round)
```

### Step 1: The deck

```php
$deck = DeckBuilder::standard52()->build();
```

Poker uses a standard 52-card deck. `DeckBuilder::standard52()` handles
the composition and returns a `Stack` with capacity 52. No wrapper class
needed.

### Step 2: The hand value object

```php
// src/Games/Poker/PokerHand.php (simplified)
namespace Likewinter\CardDeck\Games\Poker;

use Likewinter\CardDeck\Stack;
use Likewinter\CardDeck\RankOrder;

final readonly class PokerHand
{
    public const HAND_SIZE = 5;
    public HandRank $handRank;

    public function __construct(array $cards, ?RankOrder $rankOrder = null)
    {
        // ... sort, compute properties, classify hand ...
        $this->handRank = $this->classify();
    }

    public static function fromHand(Stack $hand): self { /* unwrap cards */ }
    public function compare(self $other): int { /* tiebreaker logic */ }
}
```

`PokerHand` is an immutable value object that classifies 5 cards.
It does not extend `Stack` — it's a classified snapshot, not a mutable
collection. `fromHand()` builds one from a `Stack` of dealt cards.
Classification and comparison live inside `PokerHand` — the `HandRank`
enum is a pure value it returns, not a collaborator.

### Step 3: Hand ranking

```php
// src/Games/Poker/HandRank.php
enum HandRank: int
{
    case HIGH_CARD = 0;
    case ONE_PAIR = 1;
    // ...
    case ROYAL_FLUSH = 9;

    public function getName(): string { ... }
}
```

`HandRank` is a pure enum — just the 10 ranks and their display names.
All classification and comparison logic lives in `PokerHand`, keeping
the dependency one-directional.

### Step 4: Game orchestration

```php
// src/Games/Poker.php (simplified)
namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\{DeckBuilder, Stack, Table};
use Likewinter\CardDeck\Games\Poker\PokerHand;

readonly class Poker
{
    private readonly Table $table;

    public function __construct(
        private readonly int $numHands = 3,
        ?Table $table = null,
    ) {
        $this->table = $table ?? new Table(
            deck: DeckBuilder::standard52()->build(),
            shuffle: true,
        );
        for ($i = 0; $i < $this->numHands; $i++) {
            $this->table->addHand("hand-{$i}", new Stack(capacity: PokerHand::HAND_SIZE));
        }
    }

    public function deal(): void { $this->table->drawAll(PokerHand::HAND_SIZE); }

    public function winners(): array { /* uses PokerHand::compare() */ }
    public function reset(): void { $this->table->reset(); $this->table->shuffle(); }
}
```

The `Poker` class composes the primitives: a `Table` with a shuffled
deck from `DeckBuilder`, a fixed number of named `Stack`s, and
game-specific methods (`deal`, `winners`, `reset`) that use `PokerHand`.

## Applying the pattern to other games

### Blackjack

```php
// Different deck: 6-deck shoe
$deck = DeckBuilder::standard52()->times(6)->build();

// Different rank ordering
$order = RankOrder::blackjack();

// Hand value: sum of card values, with Ace soft/hard logic
function handValue(Stack $hand, RankOrder $order): int {
    $total = 0;
    $aces = 0;
    foreach ($hand as $card) {
        $total += $order->value($card->underlyingCard()->rank);
        if ($card->underlyingCard()->rank === Rank::Ace) $aces++;
    }
    while ($total > 21 && $aces > 0) { $total -= 10; $aces--; }
    return $total;
}
```

### Bridge (trick-taking)

```php
// Standard deck, 4 players, 13 cards each
$deck = DeckBuilder::standard52()->build();
$table = new Table(deck: $deck, shuffle: true);
$table->addHand('north', new Stack(capacity: 13));
$table->addHand('east', new Stack(capacity: 13));
$table->addHand('south', new Stack(capacity: 13));
$table->addHand('west', new Stack(capacity: 13));
$table->drawAll(13);

// Trump for this hand
$suitOrder = SuitOrder::suit(Suit::Spades, RankOrder::poker());
$trick = new Trick($suitOrder, numPlayers: 4);

// Play a trick — turn order is enforced automatically
for ($i = 0; $i < 4; $i++) {
    $card = /* current player plays from their hand */;
    $trick->play($card);
}
$winner = $trick->winner();

// Winner leads the next trick — create a new Trick
$trick = new Trick($suitOrder, numPlayers: 4, startingPlayer: $winner);
```

### Solitaire (face-down)

```php
// Standard deck, shuffled
$deck = DeckBuilder::standard52()->build();
$deck->shuffle();

// Tableau columns: mix of face-down and face-up CardInPlay in a Stack
$column = new Stack();
$cards = [...$deck->takeCards(7)];
foreach ($cards as $i => $card) {
    $column->addCards(
        $i === 6 ? CardInPlay::up($card) : CardInPlay::down($card)
    );
}

// Reveal the top card when the one above is removed
$top = $column->takeTop();  // CardInPlay, face-up
// ... after removing the card above:
// $column's new top can be flipped via CardInPlay::reveal()
```

## Testing your game

The framework's test suite uses [Pest](https://pestphp.com/). A typical
game test:

```php
it('ranks a royal flush correctly', function () {
    $hand = pokerHandFromString('10♠,J♠,Q♠,K♠,A♠');
    expect($hand->handRank)->toBe(HandRank::ROYAL_FLUSH);
});
```

See the [Poker tests](../tests/Unit/PokerHandTest.php) for a full
example of testing a game implementation.

## What the framework doesn't provide

- **Scoring.** Each game scores differently. Build your own.
- **Betting.** Poker betting, Bridge bidding — these are game logic.
- **AI.** No bot players. The framework is a pure engine.
- **UI.** No rendering, no input. See `demo/poker.php` for an example
  of building a CLI presentation on top of the engine.
- **Persistence.** No save/load. Add your own serialization.
- **Networking.** No multiplayer protocol. The engine is single-process.
