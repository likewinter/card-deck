<?php

use Likewinter\CardDeck\Stack;

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

    it('cant be created with cards greater than limit', function (array $cards) {
        new Stack(cards: $cards, stackLimit: 0);
    })->with('two random cards')->throws(\InvalidArgumentException::class);

    it('can be created with cards equal to limit', function (array $cards) {
        $stack = new Stack(cards: $cards, stackLimit: 2);
        expect($stack)->toBeInstanceOf(Stack::class);
    })->with('two random cards');

    it('can be created with cards less than limit', function (array $cards) {
        $stack = new Stack(cards: $cards, stackLimit: 3);
        expect($stack)->toBeInstanceOf(Stack::class);
    })->with('two random cards');
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
});
