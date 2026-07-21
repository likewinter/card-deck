# Face-down cards

Most games keep cards face-up once played. Games with partial
information — Solitaire (tableau columns), War (face-down war cards),
any game where opponent hands are hidden — need a way to track
orientation.

`Card` itself is immutable and orientation-agnostic. `CardInPlay` wraps
a `Card` and carries a `Face` state.

## Face

```php
use Likewinter\CardDeck\Face;

Face::Up;      // visible
Face::Down;    // hidden

Face::Up->isUp();     // true
Face::Down->isDown(); // true
```

## CardInPlay

```php
use Likewinter\CardDeck\CardInPlay;

$card = new Card(Suit::Hearts, Rank::Ace);

// Construction
$inPlay = new CardInPlay($card);                  // defaults to face-up
$faceDown = CardInPlay::down($card);              // explicit face-down
$faceUp = CardInPlay::up($card);                  // explicit face-up

// Inspect
$inPlay->isFaceUp();       // bool
$inPlay->isFaceDown();     // bool
$inPlay->card;             // the underlying Card

// Flip (returns a new instance — immutable)
$revealed = $faceDown->flip();        // now face-up
$hidden = $faceUp->flip();            // now face-down

// Reveal / hide (idempotent — returns same instance if no change)
$faceDown->reveal();      // new face-up instance
$faceUp->reveal();        // same instance (already up)
```

## String representation

Face-down cards render as `██` (hidden); face-up cards render normally.

```php
echo CardInPlay::up($card);       // A♥
echo CardInPlay::down($card);     // ██
```

## When to use CardInPlay

Use `CardInPlay` when your game has cards whose visibility matters:
- **Solitaire**: tableau columns are mostly face-down, revealed as play
  progresses.
- **War**: the "war" mechanic plays 3 face-down cards + 1 face-up.
- **Any game** where you display a partial board state and some cards
  are hidden from the observer.

Use `Card` directly (without `CardInPlay`) when visibility is uniform:
- **Poker**: all dealt cards are face-down to the player but the
  framework doesn't need to model that — the hand is just a collection.
- **Bridge**: all tricks are face-up once played.

## Why a wrapper, not a Card property?

`Card` is immutable. Adding a mutable `$face` property would break
immutability and require every `Card` consumer to handle orientation
even when they don't care about it. `CardInPlay` keeps `Card` pure and
makes orientation opt-in.

## Using CardInPlay in a stack

`Stack` is typed for `Card`, not `CardInPlay`. If your game needs a
stack of face-down cards, you have two options:

1. **Keep two parallel arrays** — one of `Card` (for logic), one of
   `CardInPlay` (for display). Simple, but duplicated state.
2. **Build a FaceStack** — a small class wrapping `list<CardInPlay>`
   with similar methods to `Stack`. The framework doesn't provide this
   yet, but it's a natural extension.

For most games, option 1 is sufficient — the logic operates on `Card`
values, and the display layer tracks orientation separately.
