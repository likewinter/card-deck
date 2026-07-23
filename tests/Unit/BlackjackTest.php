<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\DeckBuilder;
use Likewinter\CardDeck\Hand;
use Likewinter\CardDeck\Table;
use Likewinter\CardDeck\Games\Blackjack;
use Likewinter\CardDeck\Card\{Rank, Suit};

it('deals 2 cards to player and dealer', function () {
    $bj = new Blackjack(numPlayers: 1);
    $bj->deal();

    expect($bj->playerCards(0)->count())->toBe(2)
        ->and($bj->dealerCards()->count())->toBe(2);
});

it('hit adds a card to the player hand', function () {
    $bj = new Blackjack(numPlayers: 1);
    $bj->deal();
    $bj->hit(0);

    expect($bj->playerCards(0)->count())->toBe(3);
});

it('dealerPlay draws until 17 or higher', function () {
    $bj = new Blackjack(numPlayers: 1);
    $bj->deal();
    $bj->dealerPlay();

    expect($bj->dealerCards()->value())->toBeGreaterThanOrEqual(17);
});

it('determines outcome correctly', function () {
    // Build a deterministic scenario
    $deck = DeckBuilder::standard52()->build();
    $table = new Table(deck: $deck);
    $bj = new Blackjack(numPlayers: 1, table: $table);

    $bj->deal();
    $bj->dealerPlay();

    $outcome = $bj->outcome(0);
    expect($outcome)->toBeIn(['win', 'lose', 'push', 'blackjack']);
});

it('rejects invalid player index', function () {
    $bj = new Blackjack(numPlayers: 1);
    $bj->playerCards(5);
})->throws(\InvalidArgumentException::class);

it('reset returns all cards to the shoe', function () {
    $bj = new Blackjack(numPlayers: 1, numDecks: 1);
    $bj->deal();
    $bj->hit(0);

    $bj->reset();

    expect($bj->playerCards(0)->count())->toBe(0)
        ->and($bj->dealerCards()->count())->toBe(0);
});
