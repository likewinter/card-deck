# Card Deck

[![CI](https://github.com/likewinter/card-deck/actions/workflows/ci.yml/badge.svg)](https://github.com/likewinter/card-deck/actions/workflows/ci.yml)
![Coverage](https://likewinter.github.io/card-deck/coverage.svg)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](https://phpstan.org/)

A PHP 8.4+ engine for building card games — the primitives, not the UI.

`likewinter/card-deck` provides game-agnostic building blocks for playing
card games: cards, decks, stacks, a table, rank/suit ordering,
trick-taking primitives, deck builders, wildcards, and face-down state.
You bring the rules; the framework brings the table.

Five reference games prove the primitives fit real, non-trivial games:
[Poker](src/Games/Poker.php) (hand ranking and tiebreakers),
[Blackjack](src/Games/Blackjack.php) (additive scoring with soft/hard
aces), [Spades](src/Games/Spades.php) (trick-taking with trump),
[Solitaire](src/Games/Solitaire.php) (face-down tableau management), and
[JokerPoker](src/Games/JokerPoker.php) (wildcard substitution with jokers).

## Requirements

- PHP 8.4 or newer

## Install

```bash
composer require likewinter/card-deck
```

## Quick start

```php
use Likewinter\CardDeck\{DeckBuilder, Stack, Table};

// Build a standard 52-card deck and shuffle it
$deck = DeckBuilder::standard52()->build();
$table = new Table(deck: $deck, shuffle: true);

// Deal 5 cards to each of 3 players
$table->addHand('alice', new Stack(capacity: 5));
$table->addHand('bob', new Stack(capacity: 5));
$table->addHand('carol', new Stack(capacity: 5));
$table->drawAll(5);

echo "Alice: {$table->hand('alice')}\n";  // Alice: A♣,K♦,Q♥,J♠,10♣
```

## What's included

| Primitive | Purpose |
|-----------|---------|
| [`Card`](src/Card.php), [`Rank`](src/Card/Rank.php), [`Suit`](src/Card/Suit.php) | Identity of a playing card |
| [`PlayableCard`](src/PlayableCard.php) | Interface for anything a Stack can hold (Card, CardInPlay, Wildcard) |
| [`Stack`](src/Stack.php) | Ordered collection of playable cards with capacity |
| [`DeckBuilder`](src/DeckBuilder.php) | Fluent factory for standard and custom decks |
| [`Table`](src/Table.php) | Orchestrates dealing, discarding, and resetting across named hands |
| [`RankOrder`](src/RankOrder.php) | Game-specific rank values and comparison |
| [`SuitOrder`](src/SuitOrder.php) | Trick-taking: trump and lead-suit rules |
| [`Trick`](src/Trick.php) | One round of play with turn order and winner determination |
| [`PlayerRing`](src/PlayerRing.php) | Rotating turn order (standalone, also used internally by Trick) |
| [`CardInPlay`](src/CardInPlay.php), [`Face`](src/Face.php) | Face-up / face-down state |
| [`Wildcard`](src/Wildcard.php) | Wildcard substitution without mutating cards |

## Documentation

- [Getting started](docs/getting-started.md) — install, first deal, the mental model
- [Cards, ranks, and suits](docs/cards.md) — `Card`, `Rank`, `Suit`, string formats
- [Stacks and decks](docs/stacks.md) — `Stack` API, capacity, `DeckBuilder` output
- [Building decks](docs/deck-builder.md) — `DeckBuilder`: standard, short, multi, custom
- [The table](docs/table.md) — dealing modes, discarding, resetting
- [Rank ordering](docs/rank-order.md) — why ranks have no intrinsic value, `RankOrder` presets
- [Trick-taking](docs/trick-taking.md) — `SuitOrder`, `Trick`, `PlayerRing`
- [Face-down cards](docs/face-down.md) — `CardInPlay`, `Face`, when to use them
- [Wildcards](docs/wildcards.md) — `Wildcard`, jokers, wild 8s, Canasta
- [Implementing a game](docs/implementing-a-game.md) — walk-through using Poker as the reference

## Game fit

The framework supports most popular card games. ✅ = directly possible
with the current primitives.

| Game | Fit | Notes |
|------|-----|-------|
| 5-Card Stud Poker | ✅ | Reference implementation in `src/Games/Poker/` |
| Texas Hold'em / Omaha | ✅ | `Table::drawAll(2)` for holes + `Stack::takeTop()` for community cards; best-5-from-7 evaluator is game logic |
| Blackjack | ✅ | `RankOrder::blackjack()` + multi-deck via `DeckBuilder::times(6)` |
| Bridge / Spades / Hearts | ✅ | `SuitOrder` + `Trick` + `PlayerRing` |
| Rummy / Gin Rummy | ✅ | `Stack::takeTop()` to draw discards, `Table::discard()` to discard, `Stack` for melds |
| War | ✅ | `CardInPlay` + `Face::Down` for the face-down war cards |
| Crazy Eights | ✅ | `Wildcard` for wild 8s |
| Euchre | ✅ | `DeckBuilder::euchre()` + trump primitives |
| Canasta | ✅ | `Wildcard` + `DeckBuilder::standard52WithJokers(4)->times(2)` |
| Solitaire (Klondike) | ✅ | `CardInPlay` + `Face::Down` for the tableau |
| Pinochle | ✅ | `DeckBuilder::pinochle()` + custom `RankOrder` |
| Skat | ✅ | Custom `RankOrder` (Jacks above Ace) + `SuitOrder` |
| Belote | ✅ | Custom `RankOrder` (J=20, 9=14, A=11, …) + `SuitOrder` |

## Design principles

1. **Game-agnostic core.** `Card`, `Stack`, `Table` know nothing about
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

## Reference games

Five reference implementations prove the primitives fit real games:

- **[Poker](src/Games/Poker/)** — `PokerHand` (immutable 5-card value
  object with classification, flush/straight detection, and full
  tiebreaker comparison), `HandRank` (10 ranks, pure enum), `Poker`
  (game orchestration: deal, hands, winners, multi-round play)
- **[Blackjack](src/Games/Blackjack.php)** — hand-value game with
  additive scoring, soft/hard ace logic, multi-deck shoe, dealer AI
- **[Spades](src/Games/Spades.php)** — trick-taking game using
  `SuitOrder`, `Trick` with enforced turn order, trick-counting scoring
- **[Solitaire](src/Games/Solitaire.php)** — Klondike solitaire with
  face-down tableau columns via `CardInPlay`/`Face`
- **[JokerPoker](src/Games/JokerPoker.php)** — 5-card poker with jokers
  as wildcards via `Wildcard` (assign/unassign, `underlyingCard()`
  resolving to the assigned card for classification)

Run the demos:

```bash
php demo/poker.php              # 3 players, 3 rounds
php demo/poker.php 5 1          # 5 players, 1 round
php demo/blackjack.php          # player vs dealer
php demo/spades.php             # 4 players, 13 tricks
php demo/solitaire.php          # Klondike solitaire
php demo/joker-poker.php        # poker with wild jokers
```

## Testing

Tests cover every primitive and all five reference games:

```bash
composer test           # Pest test suite
composer phpstan        # PHPStan level 8 static analysis
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for notable changes between releases.

## License

MIT — see [LICENSE](LICENSE).
