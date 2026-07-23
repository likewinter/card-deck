# Getting started

## Install

```bash
composer require likewinter/card-deck
```

Requires PHP 8.3 or newer.

## Your first deal

```php
use Likewinter\CardDeck\DeckBuilder;
use Likewinter\CardDeck\Table;
use Likewinter\CardDeck\Stack;

// 1. Build a deck
$deck = DeckBuilder::standard52()->build();

// 2. Create a table (pass shuffle: true to shuffle on construction)
$table = new Table(deck: $deck, shuffle: true);

// 3. Create hands and register them with the table
$table->addHand('alice', new Stack(capacity: 5));
$table->addHand('bob', new Stack(capacity: 5));

// 4. Deal 5 cards to each hand
$table->drawAll(5);

// 5. Inspect the results
echo "Alice: {$table->hand('alice')}\n";  // Alice: A♣,K♦,Q♥,J♠,10♣
echo "Bob:   {$table->hand('bob')}\n";    // Bob:   9♥,8♦,7♠,6♣,5♥
echo "Deck:  {$table->deckCount()} cards remaining\n";
```

## The mental model

The framework is built around five layers, from generic to specific:

```
Layer 1 — Identity       Card, Rank, Suit, PlayableCard
Layer 2 — Collections    Stack
Layer 3 — Orchestration  Table, DeckBuilder
Layer 4 — Game rules     RankOrder, SuitOrder, Trick, PlayerRing
Layer 5 — Your game      (e.g. Games\Poker\Poker)
```

**Layer 1** knows what a card *is* but not how to compare cards.

**Layer 2** knows how to hold, shuffle, and move cards between
collections, but not what the collections *mean* (a deck? a hand? a
discard pile? a tableau column?).

**Layer 3** coordinates dealing and discarding across multiple hands,
but doesn't know the game's rules.

**Layer 4** supplies the rules: how ranks are ordered, which suit is
trump, whose turn it is, who won a trick. These are value objects you
construct and pass around — the framework doesn't impose them.

**Layer 5** is your game. You compose the lower layers and add the
game-specific logic: scoring, betting, melds, whatever your game needs.

## Why this layering?

The key insight is that **rank ordering is game-specific**. Poker orders
ranks 2 < 3 < … < A. Blackjack values J/Q/K as 10. Belote orders
J > 9 > A > 10 > K > Q > 8 > 7. Skat puts Jacks above Aces within trump.

If `Rank` carried an integer value (as it did in an earlier version of
this library), it would silently bake in one game's ordering and break
the others. Instead, `Rank` is a pure enum — identity only — and
ordering lives in [`RankOrder`](rank-order.md), which you supply.

The same pattern applies to suits: `Suit` has no ordering, but
[`SuitOrder`](trick-taking.md) encodes trump and lead-suit rules for
trick-taking games.

## Where to go next

- [Cards, ranks, and suits](cards.md) — the identity layer
- [Stacks, decks, and hands](stacks.md) — the collection layer
- [The table](table.md) — dealing and discarding
- [Rank ordering](rank-order.md) — game-specific rank values
- [Implementing a game](implementing-a-game.md) — full walk-through
