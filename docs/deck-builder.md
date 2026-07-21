# Building decks

`DeckBuilder` is a fluent factory for constructing decks of arbitrary
composition. Most games use the standard 52-card deck, but many need
variations: jokers, multiple decks, short decks, custom rank ranges, or
duplicated cards.

## Presets

### Standard 52-card deck

```php
use Likewinter\CardDeck\DeckBuilder;

$deck = DeckBuilder::standard52()->build();
// 52 cards, 4 suits × 13 ranks, no jokers
```

### Standard deck with jokers

```php
$deck = DeckBuilder::standard52WithJokers(2)->build();
// 54 cards

// or fluently:
$deck = DeckBuilder::standard52()->withJokers(2)->build();
```

### Euchre (24 cards, 9–A)

```php
$deck = DeckBuilder::euchre()->build();
// 9, 10, J, Q, K, A in all four suits
```

### Pinochle (48 cards, 2 copies)

```php
$deck = DeckBuilder::pinochle()->build();
// Two of each 9, J, Q, K, 10, A in all four suits
```

### Piquet (32 cards, 7–A)

```php
$deck = DeckBuilder::piquet()->build();
// 7, 8, 9, 10, J, Q, K, A in all four suits
```

### Custom rank range

```php
$deck = DeckBuilder::ranging(Rank::Five, Rank::Eight)->build();
// 5, 6, 7, 8 in all four suits (16 cards)
```

## Multi-deck shoes

For games like Blackjack that use multiple decks shuffled together:

```php
$deck = DeckBuilder::standard52()->times(6)->build();
// 312 cards (6 × 52)

// Jokers are added once, not multiplied:
$deck = DeckBuilder::standard52WithJokers(2)->times(3)->build();
// 158 cards (3 × 52 + 2)
```

## Full fluent API

```php
use Likewinter\CardDeck\DeckBuilder;
use Likewinter\CardDeck\Card\{Rank, Suit};

$deck = (new DeckBuilder())
    ->suits(Suit::Hearts, Suit::Spades)       // 2 suits
    ->range(Rank::Seven, Rank::Ace)           // 7,8,9,10,J,Q,K,A
    ->withJokers(1)                           // +1 joker
    ->times(2)                                // ×2 copies
    ->build();

// 2 × 2 × 8 + 1 = 33 cards
```

### Fluent methods

| Method | Description |
|--------|-------------|
| `suits(Suit ...$s)` | Set the suits (default: none until you call this or a preset) |
| `allRanks()` | Use all 13 standard ranks |
| `ranks(Rank ...$r)` | Set specific ranks |
| `range(Rank $low, Rank $high)` | Set ranks to a consecutive range (poker ordering) |
| `withJokers(int $n = 2)` | Add $n jokers (added once, not multiplied) |
| `times(int $n)` | Repeat the base deck $n times |
| `addExtra(Card ...$c)` | Add specific extra cards |
| `build()` | Returns a `Stack` with capacity = card count |
| `buildCards()` | Returns a `list<Card>` without the Stack wrapper |

## Build output

`build()` returns a `Stack` whose capacity equals the number of cards.
`buildCards()` returns a raw `list<Card>` if you want to construct the
`Stack` yourself (e.g. with a different capacity).

```php
$cards = DeckBuilder::standard52()->buildCards();
$deck = new Stack($cards, 60);  // 52 cards but room for 60 (e.g. Canasta)
```
