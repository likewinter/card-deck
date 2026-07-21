<?php

use Likewinter\CardDeck\PlayerRing;

it('creates with a starting player', function () {
    $ring = new PlayerRing(numPlayers: 4, startingPlayer: 2);
    expect($ring->numPlayers)->toBe(4)
        ->and($ring->startingPlayer)->toBe(2)
        ->and($ring->current())->toBe(2);
});

it('rejects fewer than 2 players', function () {
    new PlayerRing(numPlayers: 1);
})->throws(\InvalidArgumentException::class);

it('rejects out-of-range starting player', function () {
    new PlayerRing(numPlayers: 4, startingPlayer: 4);
})->throws(\InvalidArgumentException::class);

it('rotates clockwise through all players', function () {
    $ring = new PlayerRing(numPlayers: 4, startingPlayer: 0);
    expect($ring->current())->toBe(0)
        ->and($ring->next())->toBe(1)
        ->and($ring->next())->toBe(2)
        ->and($ring->next())->toBe(3)
        ->and($ring->next())->toBe(0); // wraps
});

it('peekNext does not advance the pointer', function () {
    $ring = new PlayerRing(numPlayers: 3, startingPlayer: 0);
    expect($ring->current())->toBe(0)
        ->and($ring->peekNext())->toBe(1)
        ->and($ring->current())->toBe(0); // unchanged
});

it('reset returns to the starting player', function () {
    $ring = new PlayerRing(numPlayers: 4, startingPlayer: 2);
    $ring->next();
    $ring->next();
    expect($ring->current())->toBe(0);

    $ring->reset();
    expect($ring->current())->toBe(2);
});

it('setCurrent jumps to a specific player', function () {
    $ring = new PlayerRing(numPlayers: 4, startingPlayer: 0);
    $ring->setCurrent(3);
    expect($ring->current())->toBe(3);
});

it('setCurrent rejects out-of-range index', function () {
    $ring = new PlayerRing(numPlayers: 4, startingPlayer: 0);
    $ring->setCurrent(4);
})->throws(\InvalidArgumentException::class);

it('advance jumps N steps', function () {
    $ring = new PlayerRing(numPlayers: 4, startingPlayer: 0);
    expect($ring->advance(2))->toBe(2)
        ->and($ring->advance(3))->toBe(1); // wraps: 2+3=5 mod 4 = 1
});

it('advance rejects negative steps', function () {
    $ring = new PlayerRing(numPlayers: 4, startingPlayer: 0);
    $ring->advance(-1);
})->throws(\InvalidArgumentException::class);

it('handles 2-player games', function () {
    $ring = new PlayerRing(numPlayers: 2, startingPlayer: 0);
    expect($ring->next())->toBe(1)
        ->and($ring->next())->toBe(0);
});
