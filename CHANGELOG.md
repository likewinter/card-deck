# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

The upcoming 1.0.0 release transforms the library from a poker-specific
package into a game-agnostic card game framework. It includes breaking
changes to the core API, new primitives for trick-taking and
partial-information games, comprehensive bug fixes, and full
documentation.

### Added

#### Framework primitives
- **`RankOrder`** — game-specific rank ordering and values. Ships with
  `poker()`, `pokerLowAce()`, and `blackjack()` presets. Supports custom
  orderings for games like Belote, Pinochle, and Skat.
- **`Trump`** — enum (`None`, `Suit`, `NoTrump`) for trick-taking trump
  configuration.
- **`SuitOrder`** — trump and lead-suit rules with `beats()` for
  resolving "does card A beat card B in this trick?"
- **`Trick`** — records cards played in one round, determines the
  winner via `SuitOrder` + `RankOrder`.
- **`PlayerRing`** — rotating turn-order pointer with `current()`,
  `next()`, `advance()`, `reset()`.
- **`DeckBuilder`** — fluent factory for deck composition. Presets:
  `standard52()`, `standard52WithJokers()`, `euchre()`, `pinochle()`,
  `piquet()`, `ranging()`. Fluent methods: `suits()`, `range()`,
  `withJokers()`, `times()`, `addExtra()`.
- **`CardInPlay`** — immutable wrapper pairing a `Card` with a `Face`
  state (Up/Down) for partial-information games (Solitaire, War).
- **`Face`** — enum (`Up`, `Down`) for card orientation.
- **`Wildcard`** — immutable wrapper for wildcard substitution (jokers,
  wild 8s) without mutating the underlying `Card`.

#### Poker game
- **`ROYAL_FLUSH`** hand rank — distinct from `STRAIGHT_FLUSH`, ranked
  highest. Detected as an Ace-high straight flush.
- **`HandRank::compare()`** — full hand comparison with kickers and
  tiebreakers. Returns -1/0/1. Compares hand rank first, then a
  per-category tiebreaker signature (groups first, kickers descending).
- **`Poker::hands()`** — returns current hands as `PokerHand` objects
  in player order.
- **`Poker::winners()`** — returns the winning hand(s), skipping empty
  hands. Ties return all winners.
- **`Poker::winnersState()`** — formats a single winner or a tie list.
- **`Poker::reset()`** — resets the game for a new round (returns cards
  to deck and reshuffles).

#### Demo
- Multi-round tournament demo with ASCII card art, ANSI suit coloring,
  named players, per-round winner display, and a proportional-bar
  scoreboard with champion announcement. Configurable via CLI arguments
  (players 2–5, rounds ≥ 1).

#### Documentation
- **`README.md`** — project overview, quick start, primitive table,
  game-fit matrix (13 games), design principles.
- **`docs/getting-started.md`** — install, first deal, 5-layer mental
  model.
- **`docs/cards.md`** — `Card`, `Rank`, `Suit` identity layer.
- **`docs/stacks.md`** — `Stack`/`Deck`/`Hand` collection layer.
- **`docs/deck-builder.md`** — `DeckBuilder` presets and fluent API.
- **`docs/dealer.md`** — `Dealer` dealing modes, discarding, resetting.
- **`docs/rank-order.md`** — `RankOrder` presets and custom orderings.
- **`docs/trick-taking.md`** — `Trump`, `SuitOrder`, `Trick`,
  `PlayerRing`.
- **`docs/face-down.md`** — `CardInPlay`, `Face` for partial
  information.
- **`docs/wildcards.md`** — `Wildcard` for joker/wild-8 substitution.
- **`docs/implementing-a-game.md`** — 4-step pattern walk-through using
  Poker as reference, with examples for Blackjack, Bridge, and
  Solitaire.
- **`LICENSE`** — MIT license file.

#### Tests
- 294 tests (602 assertions), up from ~76 at the last origin/main
  commit. Covers every primitive and the full Poker implementation
  including all 10 straights, royal flush detection, 31 hand-comparison
  winner/loser pairs, 11 tie cases, trick resolution, and all error
  paths.

### Changed

#### Breaking changes
- **`Rank` is now a string-backed pure enum** (was int-backed with
  poker values `Joker=0, Two=2, …, Ace=14`). Ordering lives in
  `RankOrder` instead. Games that relied on `Rank::value` for
  comparison must migrate to `RankOrder::poker()->value($rank)`.
- **`Card` is identity-only.** Removed `isHigherThan()`, `isFace()`,
  `isAce()` — they embedded poker-specific assumptions. Use
  `RankOrder` for comparison.
- **`Stack::$stackLimit` → `Stack::$capacity`** (now `public readonly`,
  was `protected readonly`). Error messages updated.
- **`Deck::$deckSize` → `Deck::$capacity`** (inherited from `Stack`,
  no longer redeclared as a separate property).
- **`Hand::$handSize` → `Hand::$capacity`** in the constructor
  parameter name.
- **`Dealer` no longer shuffles the deck on construction.** New
  `bool $shuffle = false` parameter; pass `shuffle: true` for the
  previous behavior. Enables deterministic deals for testing and fixed
  scenarios.
- **`Dealer::resetGame()` no longer shuffles.** Call
  `$dealer->getDeck()->shuffle()` explicitly if reshuffling is needed.
- **`Rank::isFace()`, `Rank::isHigherThan()`, `Rank::next()`,
  `Rank::previous()`** removed — moved to `RankOrder`.
- **`Card::isHigherThan()`, `Card::isFace()`, `Card::isAce()`**
  removed — poker-specific, now handled by the game layer.

#### Non-breaking changes
- `Hand::sortByRank()` accepts an optional `?RankOrder` parameter
  (defaults to `RankOrder::poker()`).
- `PokerHand` accepts an optional `?RankOrder` parameter.
- `PokerDeck` uses `DeckBuilder::standard52()` instead of a hardcoded
  factory.
- `Poker` default `Dealer` construction passes `shuffle: true` to
  preserve existing behavior.
- Class file names normalized to PascalCase (8 files renamed:
  `dealer.php` → `Dealer.php`, etc.). Git tracks these as renames,
  preserving blame history.
- Pest upgraded from `^3.5` to `^4.2`.

### Fixed

- **`PokerHand::isSequentialRank`** now detects all 10 straights. The
  old code sorted rank *symbols as strings*, which missed 6 of 10
  (wheel A-2-3-4-5, and all straights involving a 10) because `'10'`
  sorts before `'2'` alphabetically. Now compares by `RankOrder::value()`
  with explicit wheel handling.
- **`Stack::removeCards`** no longer silently removes the wrong card.
  The old code used `array_search(..., true)` for instance identity, but
  `hasExactCards()` gates on string equivalence. When an
  equivalent-but-not-identical `Card` was passed, `array_search`
  returned `false`, and `unset($this->cards[false])` cast `false` to `0`
  and removed the card at index 0. Now matches by string equivalence
  with index tracking.
- **`Dealer::removeHands`** removes only the targeted hand. The old code
  used `array_diff` (string comparison), so two empty hands (both
  stringify to `""`) were both removed. Now uses identity-based
  `array_filter` with `!==`.
- **`Dealer::discard`** honors its `$cards` argument. The old code
  accepted `Card ...$cards` but ignored them, always moving the whole
  hand to the pile. Now discards only the specified cards when provided,
  otherwise the whole hand.
- **`Dealer::validateHand`** uses strict `in_array` comparison. Without
  it, equivalent empty `Hand` objects compared as equal under PHP's
  loose `==`, allowing operations on hands that weren't registered.
- **`Stack::moveTo` / `moveAllTo`** are now atomic. If the target
  rejects the cards (e.g. capacity exceeded), the taken cards are
  returned to the source before the exception propagates. Previously
  cards were lost to a failed move.
- **`randomCards(0)`** in the test dataset now returns 0 cards. PHP's
  `range(1, 0)` returns `[1, 0]` (a descending 2-element array), so the
  old code returned 2 cards instead of 0, corrupting the
  `'0 cards, 1 to take'` dataset.
- **Dead code in `Poker::handsState`** removed — the `handSize` check
  was already enforced by `validateConfig()` in the constructor.

### Removed

- `Rank::isFace()`, `Rank::isHigherThan()`, `Rank::next()`,
  `Rank::previous()` — moved to `RankOrder` (see Breaking changes).
- `Card::isHigherThan()`, `Card::isFace()`, `Card::isAce()` —
  poker-specific, moved to the game layer (see Breaking changes).
- Dead `handSize` check in `Poker::handsState()` — already enforced by
  `validateConfig()`.

---

## [0.1.0] - 2025-05-16

Initial pre-refactor state (commit `7756a72` on origin/main). The
library was poker-specific with `Rank` as an int-backed enum carrying
poker values. Included `Card`, `Rank`, `Suit`, `Stack`, `Deck`, `Hand`,
`Dealer`, and a basic `Games\Poker` implementation with `PokerHand` and
`HandRank` (9 ranks, no royal flush, no hand comparison).

[Unreleased]: https://github.com/likewinter/card-deck/compare/7756a72...HEAD
[0.1.0]: https://github.com/likewinter/card-deck/releases/tag/0.1.0
