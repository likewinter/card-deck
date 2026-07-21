<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};
use Likewinter\CardDeck\DeckBuilder;

it('builds a standard 52-card deck', function () {
    $deck = DeckBuilder::standard52()->build();
    expect($deck->count())->toBe(52)
        ->and($deck->capacity)->toBe(52);
});

it('standard52 has 4 suits × 13 ranks with no jokers', function () {
    $cards = DeckBuilder::standard52()->buildCards();
    expect($cards)->toHaveCount(52);

    $suits = array_unique(array_map(fn (Card $c) => $c->suit->value, $cards));
    expect($suits)->toHaveCount(4);

    $ranks = array_unique(array_map(fn (Card $c) => $c->rank->value, $cards));
    expect($ranks)->toHaveCount(13);

    $hasJoker = false;
    foreach ($cards as $card) {
        if ($card->isJoker()) {
            $hasJoker = true;
            break;
        }
    }
    expect($hasJoker)->toBeFalse();
});

it('builds a deck with jokers', function () {
    $deck = DeckBuilder::standard52WithJokers(2)->build();
    expect($deck->count())->toBe(54);

    $jokers = array_filter([...$deck], fn (Card $c) => $c->isJoker());
    expect($jokers)->toHaveCount(2);
});

it('builds a euchre deck (24 cards, 9-A)', function () {
    $cards = DeckBuilder::euchre()->buildCards();
    expect($cards)->toHaveCount(24);

    $rankNames = array_unique(array_map(fn (Card $c) => $c->rank->name, $cards));
    expect($rankNames)->toHaveCount(6)
        ->and($rankNames)->toContain('Nine')
        ->and($rankNames)->toContain('Ten')
        ->and($rankNames)->toContain('Jack')
        ->and($rankNames)->toContain('Queen')
        ->and($rankNames)->toContain('King')
        ->and($rankNames)->toContain('Ace');
});

it('builds a pinochle deck (48 cards, 2 copies)', function () {
    $cards = DeckBuilder::pinochle()->buildCards();
    expect($cards)->toHaveCount(48);

    // Pinochle uses 9, J, Q, K, 10, A — 6 ranks × 4 suits × 2 copies
    $rankNames = array_unique(array_map(fn (Card $c) => $c->rank->name, $cards));
    expect($rankNames)->toHaveCount(6);

    // Each rank-suit combination should appear exactly twice
    $freq = array_count_values(array_map(fn (Card $c) => (string) $c, $cards));
    foreach ($freq as $card => $count) {
        expect($count)->toBe(2);
    }
});

it('builds a piquet deck (32 cards, 7-A)', function () {
    $cards = DeckBuilder::piquet()->buildCards();
    expect($cards)->toHaveCount(32);

    $rankNames = array_unique(array_map(fn (Card $c) => $c->rank->name, $cards));
    expect($rankNames)->toHaveCount(8)
        ->and($rankNames)->toContain('Seven')
        ->and($rankNames)->toContain('Ace');
});

it('builds a multi-deck shoe (Blackjack 6×52 = 312 cards)', function () {
    $cards = DeckBuilder::standard52()->times(6)->buildCards();
    expect($cards)->toHaveCount(312);

    // Each card should appear exactly 6 times
    $freq = array_count_values(array_map(fn (Card $c) => (string) $c, $cards));
    foreach ($freq as $card => $count) {
        expect($count)->toBe(6);
    }
});

it('multi-deck with jokers adds jokers once (not multiplied)', function () {
    $cards = DeckBuilder::standard52WithJokers(2)->times(3)->buildCards();
    expect($cards)->toHaveCount(3 * 52 + 2); // 158

    $jokers = array_filter($cards, fn (Card $c) => $c->isJoker());
    expect($jokers)->toHaveCount(2); // not 6
});

it('builds a custom deck via fluent API', function () {
    $cards = (new DeckBuilder())
        ->suits(Suit::Hearts, Suit::Spades)
        ->range(Rank::Seven, Rank::Ace)
        ->buildCards();

    // 2 suits × 7 ranks (7,8,9,10,J,Q,K,A) = 16
    expect($cards)->toHaveCount(16);

    $suitNames = array_unique(array_map(fn (Card $c) => $c->suit->name, $cards));
    expect($suitNames)->toHaveCount(2)
        ->and($suitNames)->toContain('Hearts')
        ->and($suitNames)->toContain('Spades');
});

it('range() with low and high correctly bounds the ranks', function () {
    $cards = DeckBuilder::ranging(Rank::Five, Rank::Eight)->buildCards();
    $rankNames = array_unique(array_map(fn (Card $c) => $c->rank->name, $cards));
    expect($rankNames)->toHaveCount(4)
        ->and($rankNames)->toContain('Five')
        ->and($rankNames)->toContain('Six')
        ->and($rankNames)->toContain('Seven')
        ->and($rankNames)->toContain('Eight');
});

it('rejects invalid joker count', function () {
    DeckBuilder::standard52()->withJokers(-1);
})->throws(\InvalidArgumentException::class);

it('rejects invalid copies count', function () {
    DeckBuilder::standard52()->times(0);
})->throws(\InvalidArgumentException::class);

it('build returns a Deck with the correct capacity', function () {
    $deck = DeckBuilder::standard52()->withJokers(2)->build();
    expect($deck->count())->toBe(54)
        ->and($deck->capacity)->toBe(54);
});
