<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};

it('can be created', function () {
    $card = new Card(suit: Suit::Clubs, rank: Rank::Ace);
    expect($card)->toBeInstanceOf(Card::class);
});

it('can be converted to string', function (Suit $suit, Rank $rank, string $expected) {
    $card = new Card(suit: $suit, rank: $rank);
    expect((string) $card)->toBe($expected);
})->with([
    'ace of clubs' => [Suit::Clubs, Rank::Ace, 'A♣'],
    'two of diamonds' => [Suit::Diamonds, Rank::Two, '2♦'],
    'three of hearts' => [Suit::Hearts, Rank::Three, '3♥'],
    'four of spades' => [Suit::Spades, Rank::Four, '4♠'],
]);

describe('invalid card creation', function () {
    it('cant create joker with non-joker rank', function () {
        $card = new Card(suit: Suit::Joker, rank: Rank::Ace);
    })->throws(\InvalidArgumentException::class);

    it('cant create joker with non-joker suit', function () {
        $card = new Card(suit: Suit::Clubs, rank: Rank::Joker);
    })->throws(\InvalidArgumentException::class);
});
