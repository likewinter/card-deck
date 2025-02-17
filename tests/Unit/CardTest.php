<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};

it('can be created', function (Suit $suit, Rank $rank) {
    $card = new Card(suit: $suit, rank: $rank);
    expect($card)->toBeInstanceOf(Card::class);
})->with('cards and their string representations');

it('can be created from string', function (Suit $suit, Rank $rank, string $string) {
    $card = new Card(suit: $suit, rank: $rank);
    $cardFromString = Card::fromString($string);
    expect($cardFromString->equals($card))->toBeTrue();
})->with('cards and their string representations');

it('can be converted to string', function (Suit $suit, Rank $rank, string $expected) {
    $card = new Card(suit: $suit, rank: $rank);
    expect((string) $card)->toBe($expected);
})->with('cards and their string representations');

it('cant create joker with non-joker rank or suit', function (Suit $suit, Rank $rank) {
    new Card(suit: $suit, rank: $rank);
})->with('invalid joker cards')->throws(\InvalidArgumentException::class);


describe('card comparison', function () {
    it('can compare cards', function (Card $card1, Card $card2, bool $expected) {
        expect($card1->isHigherThan($card2))->toBe($expected);
    })->with('cards to compare');

    it('can compare equal cards', function (Card $card1, Card $card2, bool $expectedHigher, bool $expectedEquals) {
        expect($card1->equals($card2))->toBe($expectedEquals);
    })->with('cards to compare');
});
