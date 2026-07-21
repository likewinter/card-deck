# Implementing a game

This walk-through shows how to build a card game on top of the
framework, using the reference [Poker implementation](../src/Games/Poker/)
as the example. The same pattern applies to any game.

## The pattern

1. **Identify your game's needs.** What deck? How many cards per hand?
   How are ranks ordered? Is there trump? Are there wildcards?
2. **Compose the primitives.** Use `DeckBuilder` for the deck, `Hand`
   for player hands, `Dealer` for dealing, `RankOrder` for rank values.
3. **Add game-specific classes.** A `GameHand` (extending `Hand`), a
   `HandRank` enum or similar, a `Game` orchestration class.
4. **Wire it up.** The `Game` class constructs the dealer, manages
   rounds, and exposes state.

## Example: Poker

The Poker implementation lives in `src/Games/Poker/`:

```
src/Games/Poker/
├── HandRank.php      — enum of 10 poker hand ranks
├── PokerDeck.php     — 52-card deck via DeckBuilder
└── PokerHand.php     — 5-card hand with ranking and comparison
src/Games/Poker.php   — game orchestration (deal, winners, multi-round)
```

### Step 1: The deck

```php
// src/Games/Poker/PokerDeck.php
namespace Likewinter\CardDeck\Games\Poker;

use Likewinter\CardDeck\Deck;
use Likewinter\CardDeck\DeckBuilder;

class PokerDeck extends Deck
{
    public const DECK_SIZE = 52;

    public function __construct()
    {
        parent::__construct(DeckBuilder::standard52()->buildCards(), self::DECK_SIZE);
    }
}
```

Poker uses a standard 52-card deck. `DeckBuilder::standard52()` handles
the composition; we just wrap it in a `Deck` with the right capacity.

### Step 2: The hand

```php
// src/Games/Poker/PokerHand.php (simplified)
namespace Likewinter\CardDeck\Games\Poker;

use Likewinter\CardDeck\Hand;
use Likewinter\CardDeck\RankOrder;

class PokerHand extends Hand
{
    public const HAND_SIZE = 5;
    public readonly HandRank $handRank;
    public readonly bool $isSameSuit;
    public readonly bool $isSequentialRank;

    public function __construct(array $cards, ?RankOrder $rankOrder = null)
    {
        parent::__construct($cards, self::HAND_SIZE);
        $this->rankOrder = $rankOrder ?? RankOrder::poker();
        $this->sortByRank($this->rankOrder);
        // ... compute properties ...
        $this->handRank = HandRank::getRankForHand($this);
    }
}
```

`PokerHand` extends `Hand` and computes poker-specific properties
(rank sets, flush/straight detection, hand rank) in the constructor.
It accepts an optional `RankOrder` so games could theoretically use a
different ordering, but defaults to `RankOrder::poker()`.

### Step 3: Hand ranking

```php
// src/Games/Poker/HandRank.php (simplified)
enum HandRank: int
{
    case HIGH_CARD = 0;
    case ONE_PAIR = 1;
    // ...
    case ROYAL_FLUSH = 9;

    public static function getRankForHand(PokerHand $hand): self { ... }
    public static function compare(PokerHand $a, PokerHand $b): int { ... }
}
```

`HandRank` is a game-specific enum. The framework doesn't know about
poker hands — this is your game's logic. `getRankForHand()` classifies a
hand; `compare()` breaks ties using a tiebreaker signature (groups first,
then kickers, all via `RankOrder::value()`).

### Step 4: Game orchestration

```php
// src/Games/Poker.php (simplified)
namespace Likewinter\CardDeck\Games;

use Likewinter\CardDeck\Dealer;
use Likewinter\CardDeck\Hand;
use Likewinter\CardDeck\Games\Poker\{PokerDeck, PokerHand, HandRank};

readonly class Poker
{
    public function __construct(
        private readonly int $handSize = 5,
        private readonly int $numHands = 3,
        private readonly Dealer $dealer = new Dealer(deck: new PokerDeck(), shuffle: true),
    ) {
        $this->validateConfig();
        for ($i = 0; $i < $this->numHands; $i++) {
            $this->dealer->addHands(new Hand(capacity: $this->handSize));
        }
    }

    public function deal(): void { $this->dealer->drawAll($this->handSize); }

    public function winners(): array { /* uses HandRank::compare */ }
    public function reset(): void { $this->dealer->resetGame(); $this->dealer->getDeck()->shuffle(); }
}
```

The `Poker` class composes the primitives: a `Dealer` with a shuffled
`PokerDeck`, a fixed number of `Hand`s, and game-specific methods
(`deal`, `winners`, `reset`) that use `PokerHand` and `HandRank`.

## Applying the pattern to other games

### Blackjack

```php
// Different deck: 6-deck shoe
$deck = DeckBuilder::standard52()->times(6)->build();

// Different rank ordering
$order = RankOrder::blackjack();

// Hand value: sum of card values, with Ace soft/hard logic
function handValue(Hand $hand, RankOrder $order): int {
    $total = 0;
    $aces = 0;
    foreach ($hand as $card) {
        $total += $order->value($card->rank);
        if ($card->rank === Rank::Ace) $aces++;
    }
    while ($total > 21 && $aces > 0) { $total -= 10; $aces--; }
    return $total;
}
```

### Bridge (trick-taking)

```php
// Standard deck, 4 players, 13 cards each
$deck = DeckBuilder::standard52()->build();
$dealer = new Dealer(deck: $deck, shuffle: true);
// ... deal 13 to each of 4 hands ...

// Trump for this hand
$suitOrder = SuitOrder::suit(Suit::Spades);
$rankOrder = RankOrder::poker();
$ring = new PlayerRing(numPlayers: 4);
$trick = new Trick($suitOrder, $rankOrder, numPlayers: 4);

// Play a trick
for ($i = 0; $i < 4; $i++) {
    $player = $ring->current();
    $card = /* player plays from their hand */;
    $trick->play($player, $card);
    $ring->next();
}
$winner = $trick->winner();
```

### Solitaire (face-down)

```php
// Standard deck, no shuffle (or a specific shuffle for a deal)
$deck = DeckBuilder::standard52()->build();
$deck->shuffle();

// Tableau columns: mix of face-down and face-up
$column = array_map(
    fn(Card $c, int $i) => $i === 6 ? CardInPlay::up($c) : CardInPlay::down($c),
    [...$deck->takeCards(7)],
    range(0, 6)
);

// Reveal the top card when the one above is removed
$top = array_pop($column);
$column[] = end($column)->reveal();
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
