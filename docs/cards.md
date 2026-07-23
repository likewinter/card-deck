# Cards, ranks, and suits

## Card

A `Card` is an immutable pair of a `Suit` and a `Rank`. It has no
ordering, no value, and no game-specific semantics — just identity.

```php
use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};

$card = new Card(suit: Suit::Hearts, rank: Rank::Ace);
echo $card;                    // A♥
echo $card->suit->value;       // hearts
echo $card->rank->value;       // A (the display symbol)
echo $card->isJoker();         // false
```

### Construction rules

- A Joker `Suit` must be paired with a Joker `Rank` (and vice versa).
  Mixing them throws `InvalidArgumentException`.
- All properties are `readonly`. Cards are value objects — compare them
  with `equals()`, not `===`.

### String format

Cards round-trip through strings:

```php
$card = Card::fromString('10♥');
echo $card;                    // 10♥
echo $card->rank;              // 10
echo $card->suit;              // ♥
```

The format is `{rank}{suit}` where:
- Rank symbols: `2`–`9`, `10`, `J`, `Q`, `K`, `A`, `🃏` (joker)
- Suit symbols: `♥` `♦` `♣` `♠` `🃏` (joker)

Two-character ranks (`10`) are handled correctly by `fromString`.

### Comparison

`Card` itself has no ordering. To compare cards, use a `RankOrder`:

```php
use Likewinter\CardDeck\RankOrder;

$order = RankOrder::poker();
$order->isHigher($cardA->rank, $cardB->rank);  // bool
$order->compare($cardA->rank, $cardB->rank);   // -1, 0, or 1
```

See [Rank ordering](rank-order.md) for why this design exists.

### Available methods

| Method | Returns | Description |
|--------|---------|-------------|
| `equals(Card $other)` | `bool` | Same suit and rank |
| `isJoker()` | `bool` | Rank is Joker |
| `__toString()` | `string` | e.g. `A♥` |
| `Card::fromString(string)` | `self` | Parse `A♥` → Card |

## Rank

`Rank` is a pure enum — identity only, no integer value, no ordering.

```php
enum Rank: string
{
    case Joker = 'joker';
    case Two = '2';
    case Three = '3';
    // ...
    case Ten = '10';
    case Jack = 'J';
    case Queen = 'Q';
    case King = 'K';
    case Ace = 'A';
}
```

The string backing is the display symbol (except Joker, which uses
`'joker'` internally and renders as `🃏` via `getSymbol()`).

### Why no integer value?

Because different games order ranks differently. See
[Rank ordering](rank-order.md) for the full explanation and the
`RankOrder` class that supplies game-specific values.

### Available methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getSymbol()` | `string` | Display symbol (`A`, `10`, `🃏`) |
| `Rank::fromSymbol(string)` | `self` | Parse a symbol |
| `Rank::casesWithoutJoker()` | `list<Rank>` | All ranks except Joker |
| `Rank::cases()` | `list<Rank>` | All ranks including Joker (inherited) |

## Suit

`Suit` is a pure enum with five cases:

```php
enum Suit: string
{
    case Joker = 'joker';
    case Hearts = 'hearts';
    case Diamonds = 'diamonds';
    case Clubs = 'clubs';
    case Spades = 'spades';
}
```

### Available methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getSymbol()` | `string` | `♥`, `♦`, `♣`, `♠`, `🃏` |
| `getColor()` | `string` | `'red'` or `'black'` |
| `Suit::fromSymbol(string)` | `self` | Parse a symbol |
| `Suit::casesWithoutJoker()` | `list<Suit>` | Standard suits only |

Like `Rank`, `Suit` has no ordering — trump and lead-suit rules live in
`SuitOrder`. See [Trick-taking](trick-taking.md).
