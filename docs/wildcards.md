# Wildcards

Some games have cards that can stand in for another: jokers (Canasta,
joker poker), wild 8s (Crazy Eights), wild 2s (Canasta). `Wildcard`
wraps a card and tracks which card it currently represents — without
mutating the underlying `Card`.

## Why a wrapper?

`Card` is immutable — you can't change a joker's rank or suit. And you
shouldn't: the joker is still a joker; it just *acts as* a different
card for the purposes of the game. `Wildcard` models this substitution
as a separate, immutable value object.

## Wildcard

```php
use Likewinter\CardDeck\Wildcard;

$joker = new Card(Suit::Joker, Rank::Joker);
$kingOfHearts = new Card(Suit::Hearts, Rank::King);

// Create an unassigned wildcard
$wild = new Wildcard($joker);
$wild->isUnassigned();        // true
$wild->isAssigned();          // false
$wild->effective();           // null

// Assign it to stand in for the King of Hearts
$assigned = $wild->assign($kingOfHearts);
$assigned->isAssigned();      // true
$assigned->effective();       // the King of Hearts Card
$assigned->wild;              // the original joker (unchanged)

// Unassign
$unassigned = $assigned->unassign();
$unassigned->isUnassigned();  // true

// String representation
echo $wild;                   // 🃏🃏 (shows the wild card)
echo $assigned;               // K♥ (shows the assigned card)
```

## Immutability

`assign()` and `unassign()` return *new* `Wildcard` instances — they
don't mutate the original:

```php
$wild = new Wildcard($joker);
$assigned = $wild->assign($kingOfHearts);

$wild->isUnassigned();        // true  (original unchanged)
$assigned->isAssigned();      // true  (new instance)
```

This lets you track a wildcard's state at different points in a game
without losing earlier states.

## Non-joker wildcards

`Wildcard` accepts any `Card` as the wild card, not just jokers. This
supports games like Crazy Eights where 8s act as wild:

```php
$eight = new Card(Suit::Hearts, Rank::Eight);
$wild = new Wildcard($eight);

// Assign it to stand in for any card the player chooses
$wild = $wild->assign(new Card(Suit::Clubs, Rank::Ace));
echo $wild;                   // A♣
```

## Using Wildcard in a game

`Wildcard` implements `PlayableCard`, so it works directly in `Stack`
alongside regular cards:

```php
$hand = new Stack();
$hand->addCards($aceOfSpades, new Wildcard($joker));
```

Your game logic decides how to resolve wildcards to their effective
card:

```php
function effectiveCard(PlayableCard $c): Card {
    return $c instanceof Wildcard && $c->effective() !== null
        ? $c->effective()
        : ($c instanceof Wildcard ? $c->wild : $c);
}

// Check if a hand contains a specific effective card
function hasEffectiveCard(Stack $hand, Card $target): bool {
    foreach ($hand as $item) {
        if (effectiveCard($item)->equals($target)) {
            return true;
        }
    }
    return false;
}
```

The framework provides the primitive; your game provides the policy
(what counts as wild, when assignments can change, how wildcards score).
