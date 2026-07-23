<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\CardInPlay;
use Likewinter\CardDeck\Face;
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\Stack;
use Likewinter\CardDeck\Wildcard;
use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\Card\Suit;

it('can be created', function () {
    $stack = new Stack();
    expect($stack)->toBeInstanceOf(Stack::class);
});

it('can be created with cards', function (array $cards) {
    $stack = new Stack(cards: $cards);
    expect($stack)->toBeInstanceOf(Stack::class);
})->with('five random cards');

it('cant be created with invalid cards', function () {
    // @phpstan-ignore-next-line
    new Stack(cards: ['not a card']);
})->throws(\InvalidArgumentException::class);

it('can be converted to string', function (array $cards, string $stringRepresentation) {
    $stack = new Stack(cards: $cards);
    expect((string) $stack)->toBe($stringRepresentation);
})->with('cards with their string representations');

it('can be created from string', function (array $cards, string $stringRepresentation) {
    $stack = Stack::fromString($stringRepresentation);
    expect($stack)->toBeInstanceOf(Stack::class);
    expect((string)$stack)->toBe($stringRepresentation);
})->with('cards with their string representations');

it('can check if it is full', function () {
    $stack = Stack::fromString('A♣,2♦,3♥,4♠');
    expect($stack->isFull())->toBeFalse();

    $stack = Stack::fromString('A♣,2♦,3♥,4♠', capacity: 4);
    expect($stack->isFull())->toBeTrue();
});

describe('stack limit', function () {
    it('cant be created with negative limit', function () {
        new Stack(capacity: -1);
    })->throws(\InvalidArgumentException::class);

    it('cant be created with zero limit', function () {
        new Stack(capacity: 0);
    })->throws(\InvalidArgumentException::class);

    it('can be created with positive limit', function () {
        $stack = new Stack(capacity: 1);
        expect($stack)->toBeInstanceOf(Stack::class);
    });

    it('cant be created with cards greater than limit', function (array $cards) {
        new Stack(cards: $cards, capacity: 1);
    })->with('two random cards')->throws(\InvalidArgumentException::class);

    it('can be created with cards equal to limit', function (array $cards) {
        $stack = new Stack(cards: $cards, capacity: 2);
        expect($stack)->toBeInstanceOf(Stack::class);
    })->with('two random cards');

    it('can be created with cards less than limit', function (array $cards) {
        $stack = new Stack(cards: $cards, capacity: 3);
        expect($stack)->toBeInstanceOf(Stack::class);
    })->with('two random cards');
});

it('can check if it is the same as another stack', function () {
    [$stack1, $stack2] = [Stack::fromString('A♣,2♦,3♥,4♠'), Stack::fromString('A♣,2♦,3♥,4♠')];
    expect($stack1->isSame($stack2))->toBeTrue();
    expect($stack1->isSame(Stack::fromString('A♣,2♦,3♥')))->toBeFalse();
    expect($stack1->isSame(Stack::fromString('A♣,2♦,3♥,4♠,5♦')))->toBeFalse();
    expect($stack1->isSame(Stack::fromString('')))->toBeFalse();
});

describe('checking for cards', function () {
    it('returns true if card is in the stack', function (array $cards, array $cardsToFind) {
        $stack = new Stack(cards: $cards);
        expect($stack->hasCards(...$cardsToFind))->toBeTrue();
    })->with('cards to find within');

    it('returns false if card is not in the stack', function (array $cards, array $cardsNotInStack) {
        $stack = new Stack(cards: $cards);
        expect($stack->hasCards(...$cardsNotInStack))->toBeFalse();
    })->with('cards not in stack');

    it('returns true if exact cards are in the stack', function (array $cards, array $cardsToFind, bool $exact) {
        $stack = new Stack(cards: $cards);
        expect($stack->hasExactCards(...$cardsToFind))->toBe($exact);
    })->with('cards to find within');
});

describe('adding cards', function () {
    it('can add cards to the stack', function (array $cards, array $cardsToAdd) {
        $stack = new Stack(cards: $cards);
        $stack->addCards(...$cardsToAdd);
        expect($stack->count())->toBe(count($cards) + count($cardsToAdd));
        expect($stack->hasExactCards(...$cardsToAdd, ...$cards))->toBeTrue();
    })->with('cards to add to the stack');

    it('cant add cards to a full stack', function () {
        $stack = Stack::fromString('A♣,2♦,3♥,4♠', capacity: 4);
        $stack->addCards(...$stack);
    })->throws(\InvalidArgumentException::class);
});

describe('removing cards', function () {
    it('can remove cards from the stack', function (array $cards, array $cardsToRemove) {
        $stack = new Stack(cards: $cards);
        $stack->removeCards(...$cardsToRemove);
        expect($stack->count())->toBe(count($cards) - count($cardsToRemove));
        expect($stack)->not->toContain($cardsToRemove);
    })->with('cards to remove from the stack');

    it('cant remove cards that are not in the stack', function (array $cards, array $cardsNotInStack) {
        $stack = new Stack(cards: $cards);
        $stack->removeCards(...$cardsNotInStack);
    })->with('cards not in stack')->throws(\InvalidArgumentException::class);
});

describe('taking cards', function () {
    it('can take cards from the top', function (array $cards, int $num) {
        $stack = new Stack(cards: $cards);
        $taken = $stack->takeCards($num);
        expect($taken)->toBeInstanceOf(Stack::class);
        expect($stack->count())->toBe(count($cards) - $num);
        expect($taken->count())->toBe($num);
    })->with('cards to take from the top');

    it('cant take more cards than are in the stack', function (array $cards, int $num) {
        $stack = new Stack(cards: $cards);
        $stack->takeCards(count($cards) + 1);
    })->with('not enought cards to take')->throws(\InvalidArgumentException::class);
});

describe('moving cards', function () {
    it('can move all cards to another stack', function (array $cards) {
        [$source, $target] = [new Stack(cards: $cards), new Stack()];
        $source->moveAllTo($target);
        expect($source->count())->toBe(0);
        expect($target->count())->toBe(count($cards));
    })->with('cards to move');

    it('can move N cards to another stack', function (array $cards, int $num) {
        [$source, $target] = [new Stack(cards: $cards), new Stack()];
        $source->moveTo($target, $num);
        expect($source->count())->toBe(count($cards) - $num);
        expect($target->count())->toBe($num);
    })->with('cards to move');

    it('can move arbitrary cards to another stack', function (array $cards, int $num) {
        [$source, $target] = [new Stack(cards: $cards), new Stack()];
        $source->moveCardsTo($target, ...$source->peek($num));
        expect($source->count())->toBe(count($cards) - $num);
        expect($target->count())->toBe($num);
    })->with('cards to move');
});

describe('PlayableCard support', function () {
    it('holds CardInPlay alongside plain Cards', function () {
        $ace = new Card(suit: Suit::Spades, rank: Rank::Ace);
        $faceDown = CardInPlay::down(new Card(suit: Suit::Hearts, rank: Rank::King));

        $stack = new Stack(cards: [$ace, $faceDown]);

        expect($stack->count())->toBe(2)
            ->and((string) $stack)->toBe('A♠,██');
    });

    it('holds Wildcard alongside plain Cards', function () {
        $joker = new Card(suit: Suit::Joker, rank: Rank::Joker);
        $wild = new Wildcard($joker);
        $ace = new Card(suit: Suit::Spades, rank: Rank::Ace);

        $stack = new Stack(cards: [$wild, $ace]);

        expect($stack->count())->toBe(2)
            ->and($stack->hasCards($wild))->toBeTrue();
    });

    it('removes CardInPlay by identity', function () {
        $faceDown = CardInPlay::down(new Card(suit: Suit::Hearts, rank: Rank::King));
        $ace = new Card(suit: Suit::Spades, rank: Rank::Ace);

        $stack = new Stack(cards: [$faceDown, $ace]);
        $stack->removeCards($faceDown);

        expect($stack->count())->toBe(1)
            ->and((string) $stack)->toBe('A♠');
    });

    it('moves CardInPlay between stacks', function () {
        $faceDown = CardInPlay::down(new Card(suit: Suit::Hearts, rank: Rank::King));
        $source = new Stack(cards: [$faceDown]);
        $target = new Stack();

        $source->moveAllTo($target);

        expect($source->count())->toBe(0)
            ->and($target->count())->toBe(1);
    });

    it('assigned Wildcard shows its effective card in string', function () {
        $joker = new Card(suit: Suit::Joker, rank: Rank::Joker);
        $wild = (new Wildcard($joker))->assign(new Card(suit: Suit::Spades, rank: Rank::Ace));

        $stack = new Stack(cards: [$wild]);

        expect((string) $stack)->toBe('A♠')
            ->and($stack->count())->toBe(1);
    });

    it('underlyingCard resolves through wrappers', function () {
        $card = new Card(suit: Suit::Hearts, rank: Rank::Queen);
        $faceDown = CardInPlay::down($card);
        $joker = new Card(suit: Suit::Joker, rank: Rank::Joker);
        $wild = (new Wildcard($joker))->assign($card);

        expect($card->underlyingCard())->toBe($card)
            ->and($faceDown->underlyingCard())->toBe($card)
            ->and($wild->underlyingCard())->toBe($card);
    });

    it('distinguishes face-down cards wrapping different cards', function () {
        $downA = CardInPlay::down(new Card(suit: Suit::Spades, rank: Rank::Ace));
        $downK = CardInPlay::down(new Card(suit: Suit::Hearts, rank: Rank::King));

        $stack = new Stack(cards: [$downA, $downK]);
        $stack->removeCards($downA);

        expect($stack->count())->toBe(1)
            ->and($stack->hasCards($downK))->toBeTrue()
            ->and($stack->hasCards($downA))->toBeFalse();
    });

    it('distinguishes face-up and face-down of the same card', function () {
        $card = new Card(suit: Suit::Spades, rank: Rank::Ace);
        $up = CardInPlay::up($card);
        $down = CardInPlay::down($card);

        expect($up->equals($down))->toBeFalse()
            ->and($down->equals($up))->toBeFalse();
    });

    it('distinguishes a Wildcard from the card it represents', function () {
        $joker = new Card(suit: Suit::Joker, rank: Rank::Joker);
        $wild = (new Wildcard($joker))->assign(new Card(suit: Suit::Spades, rank: Rank::Ace));
        $real = new Card(suit: Suit::Spades, rank: Rank::Ace);

        expect($wild->equals($real))->toBeFalse()
            ->and($real->equals($wild))->toBeFalse();
    });
});

it('moveTo rolls back on target capacity exceeded', function () {
    $source = Stack::fromString('A♣,2♦,3♥');
    $target = new Stack(capacity: 2);

    try {
        $source->moveTo($target, 3);
        expect(false)->toBeTrue('Expected exception was not thrown');
    } catch (\InvalidArgumentException $e) {
        // Expected — target can't hold 3 cards
    }

    expect($source->count())->toBe(3)
        ->and($target->count())->toBe(0);
});

it('moveAllTo rolls back on target capacity exceeded', function () {
    $source = Stack::fromString('A♣,2♦,3♥');
    $target = new Stack(capacity: 2);

    try {
        $source->moveAllTo($target);
        expect(false)->toBeTrue('Expected exception was not thrown');
    } catch (\InvalidArgumentException $e) {
        // Expected
    }

    expect($source->count())->toBe(3)
        ->and($target->count())->toBe(0);
});

it('sorts by rank using the given RankOrder', function () {
    $stack = new Stack(cards: [
        new Card(suit: Suit::Hearts, rank: Rank::King),
        new Card(suit: Suit::Spades, rank: Rank::Two),
        new Card(suit: Suit::Clubs, rank: Rank::Ace),
    ], capacity: 5);

    $stack->sortByRank(RankOrder::poker());

    $ranks = array_map(fn(Card $c) => $c->rank, [...$stack]);
    expect($ranks)->toBe([Rank::Two, Rank::King, Rank::Ace]);
});

it('sorts by a custom RankOrder', function () {
    $stack = new Stack(cards: [
        new Card(suit: Suit::Hearts, rank: Rank::King),
        new Card(suit: Suit::Spades, rank: Rank::Two),
        new Card(suit: Suit::Clubs, rank: Rank::Ace),
    ], capacity: 5);

    $stack->sortByRank(RankOrder::pokerLowAce());

    $ranks = array_map(fn(Card $c) => $c->rank, [...$stack]);
    expect($ranks)->toBe([Rank::Ace, Rank::Two, Rank::King]);
});

it('extracts ranks from all cards', function () {
    $stack = new Stack(cards: [
        new Card(suit: Suit::Hearts, rank: Rank::Ace),
        new Card(suit: Suit::Spades, rank: Rank::King),
    ], capacity: 5);

    expect($stack->getRanks())->toBe([Rank::Ace, Rank::King]);
});

it('extracts suits from all cards', function () {
    $stack = new Stack(cards: [
        new Card(suit: Suit::Hearts, rank: Rank::Ace),
        new Card(suit: Suit::Spades, rank: Rank::King),
    ], capacity: 5);

    expect($stack->getSuits())->toBe([Suit::Hearts, Suit::Spades]);
});
