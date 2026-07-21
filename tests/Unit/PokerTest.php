<?php

use Likewinter\CardDeck\Games\Poker;

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

it('rejects invalid hand size', function () {
    new Poker(handSize: 7);
})->throws(\InvalidArgumentException::class);

it('reports game state', function () {
    $poker = new Poker(handSize: 5, numHands: 3);
    $state = $poker->gameState();
    expect($state)->toContain('Number of hands: 3')
        ->and($state)->toContain('Hand size: 5')
        ->and($state)->toContain('Deck size: 52');
});

it('deals cards and reports hands state with one rank per hand', function () {
    $poker = new Poker(handSize: 5, numHands: 3);
    $poker->deal();

    $handsState = $poker->handsState();
    expect($handsState)->toContain('Hands:')
        ->and($handsState)->toContain('->')
        ->and(substr_count($handsState, '->'))->toBe(3);
});
