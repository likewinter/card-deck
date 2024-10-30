<?php

use Likewinter\CardDeck\{Card,Stack};
use Likewinter\CardDeck\Card\{Rank,Suit};

it('can be created', function () {
    $stack = new Stack();
    expect($stack)->toBeInstanceOf(Stack::class);
});

it('can be created with cards', function () {
    $stack = new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace)]);
    expect($stack)->toBeInstanceOf(Stack::class);
});

it('cant be created with invalid cards', function () {
    // @phpstan-ignore-next-line
    new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace), 'not a card']);
})->throws(\InvalidArgumentException::class);

it('can be converted to string', function () {
    $stack = new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace)]);
    expect((string) $stack)->toBe('A♣');
});

describe('stack limit', function () {
    it('cant be created with negative limit', function () {
        new Stack(stackLimit: -1);
    })->throws(\InvalidArgumentException::class);

    it('cant be created with zero limit', function () {
        new Stack(stackLimit: 0);
    })->throws(\InvalidArgumentException::class);

    it('can be created with positive limit', function () {
        $stack = new Stack(stackLimit: 1);
        expect($stack)->toBeInstanceOf(Stack::class);
    });

    it('cant be created with cards greater than limit', function () {
        new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace)], stackLimit: 0);
    })->throws(\InvalidArgumentException::class);

    it('can be created with cards equal to limit', function () {
        $stack = new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace)], stackLimit: 1);
        expect($stack)->toBeInstanceOf(Stack::class);
    });

    it('can be created with cards less than limit', function () {
        $stack = new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace)], stackLimit: 2);
        expect($stack)->toBeInstanceOf(Stack::class);
    });
});

describe('checking for cards', function () {
    $cards = [new Card(suit: Suit::Clubs, rank: Rank::Ace), new Card(suit: Suit::Diamonds, rank: Rank::Two), new Card(suit: Suit::Hearts, rank: Rank::Three)];
    $cardsNotInStack = [new Card(suit: Suit::Spades, rank: Rank::Four), new Card(suit: Suit::Clubs, rank: Rank::Five), new Card(suit: Suit::Hearts, rank: Rank::Six)];

    it('returns true if card is in the stack', function (Card $card) use ($cards) {
        $stack = new Stack(cards: $cards);
        expect($stack->hasCards($card))->toBeTrue();
    })->with($cards);

    it('returns false if card is not in the stack', function (Card $card) use ($cards) {
        $stack = new Stack(cards: $cards);
        expect($stack->hasCards($card))->toBeFalse();
    })->with($cardsNotInStack);
});

describe('taking cards', function () {
    it('can take top cards', function () {
        $stack = new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace), new Card(suit: Suit::Diamonds, rank: Rank::Two), new Card(suit: Suit::Hearts, rank: Rank::Three)]);
        $stack->takeTopCards(2);
        expect($stack->count())->toBe(1);
    });

    it('can take all cards', function () {
        $stack = new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace), new Card(suit: Suit::Diamonds, rank: Rank::Two), new Card(suit: Suit::Hearts, rank: Rank::Three)]);
        $stack->takeTopCards(3);
        expect($stack->count())->toBe(0);
    });

    it('cant take more cards than available', function () {
        $stack = new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace), new Card(suit: Suit::Diamonds, rank: Rank::Two), new Card(suit: Suit::Hearts, rank: Rank::Three)]);
        $stack->takeTopCards(4);
    })->throws(\InvalidArgumentException::class);

    it('returns new stack with taken cards', function () {
        $stack = new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace), new Card(suit: Suit::Diamonds, rank: Rank::Two), new Card(suit: Suit::Hearts, rank: Rank::Three)]);
        $newStack = $stack->takeTopCards(2);
        expect($newStack)->toBeInstanceOf(Stack::class);
        expect($newStack->count())->toBe(2);
        expect($newStack)->toEqual(new Stack([new Card(suit: Suit::Clubs, rank: Rank::Ace), new Card(suit: Suit::Diamonds, rank: Rank::Two)]));
    });

    it('removes taken cards from original stack', function () {
        $stack = new Stack(cards: [new Card(suit: Suit::Clubs, rank: Rank::Ace), new Card(suit: Suit::Diamonds, rank: Rank::Two), new Card(suit: Suit::Hearts, rank: Rank::Three)]);
        $stack->takeTopCards(2);
        expect($stack->count())->toBe(1);
        expect($stack)->toEqual(new Stack([new Card(suit: Suit::Hearts, rank: Rank::Three)]));
    });
});
