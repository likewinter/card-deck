<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};

it('can be created', function (Suit $suit, Rank $rank) {
    $card = new Card(suit: $suit, rank: $rank);
    expect($card)->toBeInstanceOf(Card::class);
})->with('cards and their string representations');

it('can be converted to string', function (Suit $suit, Rank $rank, string $expected) {
    $card = new Card(suit: $suit, rank: $rank);
    expect((string) $card)->toBe($expected);
})->with('cards and their string representations');


it('cant create joker with non-joker rank or sut', function (Suit $suit, Rank $rank) {
    $card = new Card(suit: $suit, rank: $rank);
})->with('invalid joker cards')->throws(\InvalidArgumentException::class);
