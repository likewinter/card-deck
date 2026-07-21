<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Hand;
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\Card\Suit;

it('can be created with a capacity', function () {
    $hand = new Hand(capacity: 7);
    expect($hand->capacity)->toBe(7)
        ->and($hand->count())->toBe(0);
});

it('defaults to capacity 5', function () {
    $hand = new Hand();
    expect($hand->capacity)->toBe(5);
});

it('sorts by rank using the given RankOrder', function () {
    $hand = new Hand(cards: [
        new Card(suit: Suit::Hearts, rank: Rank::King),
        new Card(suit: Suit::Spades, rank: Rank::Two),
        new Card(suit: Suit::Clubs, rank: Rank::Ace),
    ], capacity: 5);

    $hand->sortByRank(RankOrder::poker());

    $ranks = array_map(fn(Card $c) => $c->rank, [...$hand]);
    expect($ranks)->toBe([Rank::Two, Rank::King, Rank::Ace]);
});

it('sorts by a custom RankOrder', function () {
    $hand = new Hand(cards: [
        new Card(suit: Suit::Hearts, rank: Rank::King),
        new Card(suit: Suit::Spades, rank: Rank::Two),
        new Card(suit: Suit::Clubs, rank: Rank::Ace),
    ], capacity: 5);

    $hand->sortByRank(RankOrder::pokerLowAce());

    $ranks = array_map(fn(Card $c) => $c->rank, [...$hand]);
    expect($ranks)->toBe([Rank::Ace, Rank::Two, Rank::King]);
});

it('extracts ranks from all cards', function () {
    $hand = new Hand(cards: [
        new Card(suit: Suit::Hearts, rank: Rank::Ace),
        new Card(suit: Suit::Spades, rank: Rank::King),
    ], capacity: 5);

    expect($hand->getRanks())->toBe([Rank::Ace, Rank::King]);
});

it('extracts suits from all cards', function () {
    $hand = new Hand(cards: [
        new Card(suit: Suit::Hearts, rank: Rank::Ace),
        new Card(suit: Suit::Spades, rank: Rank::King),
    ], capacity: 5);

    expect($hand->getSuits())->toBe([Suit::Hearts, Suit::Spades]);
});
