<?php

use Likewinter\CardDeck\Games\Poker;
use Likewinter\CardDeck\Games\Poker\PokerHand;

it('can be created with defaults', function () {
    $poker = new Poker();
    expect($poker)->toBeInstanceOf(Poker::class);
});

it('rejects too few hands', function () {
    new Poker(numHands: 1);
})->throws(\InvalidArgumentException::class);

it('rejects too many hands', function () {
    new Poker(numHands: 6);
})->throws(\InvalidArgumentException::class);

it('returns no hands before dealing', function () {
    $poker = new Poker(numHands: 3);
    expect($poker->hands())->toBeEmpty();
});

it('returns no winners before dealing', function () {
    $poker = new Poker(numHands: 3);
    expect($poker->winners())->toBeEmpty();
});

it('deals cards and returns PokerHand objects in player order', function () {
    $poker = new Poker(numHands: 3);
    $poker->deal();

    $hands = $poker->hands();
    expect($hands)->toHaveCount(3);
    foreach ($hands as $hand) {
        expect($hand)->toBeInstanceOf(PokerHand::class)
            ->and($hand->count())->toBe(5);
    }
});

it('returns exactly one winner after dealing', function () {
    $poker = new Poker(numHands: 3);
    $poker->deal();

    $winners = $poker->winners();
    expect($winners)->toHaveCount(1);
});

it('can reset and deal a new round', function () {
    $poker = new Poker(numHands: 2);
    $poker->deal();
    expect($poker->hands())->toHaveCount(2)
        ->and($poker->winners())->not->toBeEmpty();

    $poker->reset();

    expect($poker->hands())->toBeEmpty()
        ->and($poker->winners())->toBeEmpty();

    $poker->deal();

    expect($poker->hands())->toHaveCount(2)
        ->and($poker->winners())->not->toBeEmpty();
});

it('reset is idempotent on an undealt game', function () {
    $poker = new Poker(numHands: 2);
    $poker->reset();
    expect($poker->hands())->toBeEmpty()
        ->and($poker->winners())->toBeEmpty();
});
