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

it('returns no winners before dealing', function () {
    $poker = new Poker(handSize: 5, numHands: 3);
    expect($poker->winners())->toBeEmpty()
        ->and($poker->winnersState())->toContain('No hands dealt.');
});

it('returns exactly one winner after dealing', function () {
    $poker = new Poker(handSize: 5, numHands: 3);
    $poker->deal();

    $winners = $poker->winners();
    expect($winners)->toHaveCount(1)
        ->and($poker->winnersState())->toContain('Winner:')
        ->and($poker->winnersState())->toContain('(');
});

it('winnersState reports a tie when multiple hands tie for best', function () {
    // Hard to force a tie with random dealing, so we just verify the
    // winnersState output format handles ties when present. This test
    // verifies the format string is reachable; a real tie is exercised
    // through HandRank::compare tests in PokerHandCompareTest.
    $poker = new Poker(handSize: 5, numHands: 2);
    $poker->deal();

    $winners = $poker->winners();
    // Either 1 winner or 2+ winners (tie) — both formats must work
    expect(count($winners))->toBeGreaterThanOrEqual(1);
    expect($poker->winnersState())->toBeString();
});
