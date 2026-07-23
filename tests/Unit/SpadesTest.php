<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Stack;
use Likewinter\CardDeck\Games\Spades;
use Likewinter\CardDeck\Card\{Rank, Suit};

it('deals 13 cards to each of 4 players', function () {
    $spades = new Spades();
    $spades->deal();

    for ($p = 0; $p < 4; $p++) {
        expect($spades->hand($p)->count())->toBe(13);
    }
});

it('plays a full hand of 13 tricks', function () {
    $spades = new Spades();
    $spades->deal();

    $scores = $spades->playHand(
        function (int $player, Stack $hand, ?Suit $leadSuit): Card {
            $cards = [...$hand];
            if ($leadSuit !== null) {
                $suited = array_filter($cards, fn(Card $c) => $c->suit === $leadSuit);
                if (!empty($suited)) {
                    return array_values($suited)[0];
                }
            }
            return $cards[0];
        }
    );

    // All 13 tricks must be accounted for
    expect(array_sum($scores))->toBe(13);
});

it('empties all hands after playing', function () {
    $spades = new Spades();
    $spades->deal();

    $spades->playHand(
        fn(int $player, Stack $hand, ?Suit $leadSuit): Card => [...$hand][0]
    );

    for ($p = 0; $p < 4; $p++) {
        expect($spades->hand($p)->count())->toBe(0);
    }
});

it('reset restores the deck for a new hand', function () {
    $spades = new Spades();
    $spades->deal();

    $spades->playHand(
        fn(int $player, Stack $hand, ?Suit $leadSuit): Card => [...$hand][0]
    );

    $spades->reset();
    $spades->deal();

    for ($p = 0; $p < 4; $p++) {
        expect($spades->hand($p)->count())->toBe(13);
    }
});
