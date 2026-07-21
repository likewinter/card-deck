# Card Deck

[![CI](https://github.com/likewinter/card-deck/actions/workflows/ci.yml/badge.svg)](https://github.com/likewinter/card-deck/actions/workflows/ci.yml)

A PHP 8.4+ engine for building card games — the primitives, not the UI.

`likewinter/card-deck` provides game-agnostic building blocks for playing
card games: cards, decks, hands, stacks, a dealer, rank/suit ordering,
trick-taking primitives, deck builders, wildcards, and face-down state.
You bring the rules; the framework brings the table.

A reference [Poker](src/Games/Poker.php) implementation proves the
primitives fit a real, non-trivial game — including hand ranking, royal
flush detection, and full tiebreaker comparison.

## Requirements

- PHP 8.4 or newer

## Install

```bash
composer require likewinter/card-deck
```

## Quick start

```php
use Likewinter\CardDeck\DeckBuilder;
use Likewinter\CardDeck\Dealer;
use Likewinter\CardDeck\Hand;

// Build a standard 52-card deck and shuffle it
$deck = DeckBuilder::standard52()->build();
$deck->shuffle();

// Deal 5 cards to each of 3 players
$alice = new Hand(capacity: 5);
$bob   = new Hand(capacity: 5);
$carol = new Hand(capacity: 5);

$dealer = new Dealer(deck: $deck);
$dealer->addHands($alice, $bob, $carol);
$dealer->drawAll(5);  // sequential by default

echo "Alice: {$alice}\n";  // Alice: A♣,K♦,Q♥,J♠,10♣
```

## What's included

| Primitive | Purpose |
|-----------|---------|
| [`Card`](src/Card.php), [`Rank`](src/Card/Rank.php), [`Suit`](src/Card/Suit.php) | Identity of a playing card |
| [`PlayableCard`](src/PlayableCard.php) | Interface for anything a Stack can hold (Card, CardInPlay, Wildcard) |
| [`Stack`](src/Stack.php) | Ordered collection of playable cards with capacity |
| [`Hand`](src/Hand.php) | Specialized stack for a player's hand |
| [`DeckBuilder`](src/DeckBuilder.php) | Fluent factory for standard and custom decks |
| [`Dealer`](src/Dealer.php) | Orchestrates dealing, discarding, and resetting |
| [`RankOrder`](src/RankOrder.php) | Game-specific rank values and comparison |
| [`SuitOrder`](src/SuitOrder.php), [`Trump`](src/Trump.php) | Trick-taking: trump and lead-suit rules |
| [`Trick`](src/Trick.php) | One round of play with turn order and winner determination |
| [`PlayerRing`](src/PlayerRing.php) | Rotating turn order (standalone, also used internally by Trick) |
| [`CardInPlay`](src/CardInPlay.php), [`Face`](src/Face.php) | Face-up / face-down state |
| [`Wildcard`](src/Wildcard.php) | Wildcard substitution without mutating cards |

## Documentation

- [Getting started](docs/getting-started.md) — install, first deal, the mental model
- [Cards, ranks, and suits](docs/cards.md) — `Card`, `Rank`, `Suit`, string formats
- [Stacks, decks, and hands](docs/stacks.md) — `Stack` API, `Deck`, `Hand`, capacity
- [Building decks](docs/deck-builder.md) — `DeckBuilder`: standard, short, multi, custom
- [The dealer](docs/dealer.md) — dealing modes, discarding, resetting
- [Rank ordering](docs/rank-order.md) — why ranks have no intrinsic value, `RankOrder` presets
- [Trick-taking](docs/trick-taking.md) — `Trump`, `SuitOrder`, `Trick`, `PlayerRing`
- [Face-down cards](docs/face-down.md) — `CardInPlay`, `Face`, when to use them
- [Wildcards](docs/wildcards.md) — `Wildcard`, jokers, wild 8s, Canasta
- [Implementing a game](docs/implementing-a-game.md) — walk-through using Poker as the reference

## Game fit

The framework supports most popular card games. ✅ = directly possible,
⚠️ = possible with minor additions, ❌ = not yet.

| Game | Fit | Notes |
|------|-----|-------|
| 5-Card Stud Poker | ✅ | Reference implementation in `src/Games/Poker/` |
| Texas Hold'em / Omaha | ⚠️ | Needs community-card slots in `Dealer` |
| Blackjack | ✅ | `RankOrder::blackjack()` + multi-deck via `DeckBuilder::times(6)` |
| Bridge / Spades / Hearts | ✅ | `Trump` + `SuitOrder` + `Trick` + `PlayerRing` |
| Rummy / Gin Rummy | ⚠️ | Needs draw-from-pile API in `Dealer` |
| War | ✅ | `CardInPlay` + `Face::Down` for the face-down war cards |
| Crazy Eights | ✅ | `Wildcard` for wild 8s |
| Euchre | ✅ | `DeckBuilder::euchre()` + trump primitives |
| Canasta | ✅ | `Wildcard` + `DeckBuilder::standard52WithJokers(4)->times(2)` |
| Solitaire (Klondike) | ✅ | `CardInPlay` + `Face::Down` for the tableau |
| Pinochle | ✅ | `DeckBuilder::pinochle()` + custom `RankOrder` |
| Skat | ✅ | Custom `RankOrder` (Jacks above Ace) + `SuitOrder` |
| Belote | ✅ | Custom `RankOrder` (J=20, 9=14, A=11, …) + `SuitOrder` |

## Design principles

1. **Game-agnostic core.** `Card`, `Stack`, `Dealer` know nothing about
   poker, bridge, or blackjack. Game-specific ordering lives in
   `RankOrder` and `SuitOrder`, supplied by the game.
2. **Immutable where it matters.** `Card`, `Rank`, `Suit`, `RankOrder`,
   `SuitOrder`, `CardInPlay`, and `Wildcard` are immutable. Mutable state
   (card collections) lives in `Stack` and its subclasses.
3. **Composable, not prescriptive.** The framework gives you primitives;
   you assemble them into a game. There is no `Game` base class to extend.
4. **No UI, no I/O, no persistence.** Pure domain logic. Rendering,
   networking, and storage are the consumer's responsibility.
5. **Honest about limits.** Capacity is enforced. Failed moves roll back.
   Cards can't be lost to a thrown exception.

## Reference game: Poker

The [Poker implementation](src/Games/Poker/) is the proof that the
primitives fit a real game. It includes:

- `PokerHand` — 5-card hand with rank sets, flush/straight detection,
  hand classification, and full tiebreaker comparison via `compare()`
- `HandRank` — 10 ranks from High Card to Royal Flush (pure enum)
- `Poker` — game orchestration: deal, hands, winners, multi-round play

Run the demo:

```bash
php demo/poker.php              # 3 players, 3 rounds
php demo/poker.php 5 1          # 5 players, 1 round
```

## Testing

The framework has 302 passing tests covering every primitive and the
reference Poker implementation:

```bash
composer test           # Pest test suite
composer phpstan        # PHPStan level 8 static analysis
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for notable changes between releases.

## License

MIT — see [LICENSE](LICENSE).
