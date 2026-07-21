<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Games\Poker\HandRank;
use Likewinter\CardDeck\Games\Poker\PokerHand;

function pokerHandFromStringForCompare(string $cards): PokerHand
{
    return new PokerHand(array_map(
        fn (string $c) => Card::fromString(trim($c)),
        explode(',', $cards)
    ));
}

/**
 * [winner_cards, loser_cards, label] — winner should beat loser.
 */
dataset('hand comparison winner beats loser', function () {
    return [
        // Different ranks — higher rank beats lower
        'pair beats high card' => ['A♣,A♦,K♥,Q♠,J♣', 'A♣,K♦,Q♥,J♠,9♣'],
        'two pair beats one pair' => ['A♣,A♦,K♥,K♠,Q♣', 'A♣,A♦,K♥,Q♠,J♣'],
        'three of a kind beats two pair' => ['A♣,A♦,A♥,K♠,Q♣', 'A♣,A♦,K♥,K♠,Q♣'],
        'straight beats three of a kind' => ['2♣,3♦,4♥,5♠,6♣', 'A♣,A♦,A♥,K♠,Q♣'],
        'flush beats straight' => ['2♣,5♣,7♣,9♣,K♣', '2♣,3♦,4♥,5♠,6♣'],
        'full house beats flush' => ['A♣,A♦,A♥,K♠,K♣', '2♣,5♣,7♣,9♣,K♣'],
        'four of a kind beats full house' => ['A♣,A♦,A♥,A♠,K♣', 'A♣,A♦,A♥,K♠,K♣'],
        'straight flush beats four of a kind' => ['5♠,6♠,7♠,8♠,9♠', 'A♣,A♦,A♥,A♠,K♣'],
        'royal flush beats straight flush' => ['10♠,J♠,Q♠,K♠,A♠', '5♠,6♠,7♠,8♠,9♠'],
        'royal flush beats four of a kind' => ['10♠,J♠,Q♠,K♠,A♠', 'A♣,A♦,A♥,A♠,K♣'],

        // Same rank, tiebreakers
        'high pair beats low pair' => ['K♣,K♦,Q♥,J♠,10♣', 'Q♣,Q♦,J♥,10♠,9♣'],
        'same pair, better kicker wins' => ['A♣,A♦,K♥,Q♠,J♣', 'A♣,A♦,K♥,Q♠,9♣'],
        'same pair, kickers compared in order' => ['A♣,A♦,K♥,Q♠,J♣', 'A♣,A♦,K♥,J♠,10♣'],
        'two pair: higher top pair wins' => ['A♣,A♦,K♥,K♠,Q♣', 'K♣,K♦,Q♥,Q♠,J♣'],
        'two pair: same top, higher bottom wins' => ['A♣,A♦,K♥,K♠,Q♣', 'A♣,A♦,Q♥,Q♠,J♣'],
        'two pair: same pairs, kicker decides' => ['A♣,A♦,K♥,K♠,Q♣', 'A♣,A♦,K♥,K♠,J♣'],
        'trips: higher three wins' => ['K♣,K♦,K♥,A♠,Q♣', 'Q♣,Q♦,Q♥,A♠,K♣'],
        'trips: same three, kicker decides' => ['A♣,A♦,A♥,K♠,Q♣', 'A♣,A♦,A♥,K♠,J♣'],
        'trips: same three, second kicker decides' => ['A♣,A♦,A♥,K♠,Q♣', 'A♣,A♦,A♥,Q♠,J♣'],
        'straight: higher wins' => ['6♣,7♦,8♥,9♠,10♣', '2♣,3♦,4♥,5♠,6♣'],
        'straight: wheel loses to 6-high' => ['2♣,3♦,4♥,5♠,6♣', 'A♣,2♦,3♥,4♠,5♣'],
        'straight: broadway beats 9-high' => ['10♣,J♦,Q♥,K♠,A♣', '5♣,6♦,7♥,8♠,9♣'],
        'flush: higher card wins' => ['A♣,K♣,J♣,9♣,8♣', 'K♣,Q♣,J♣,9♣,8♣'],
        'flush: second card decides' => ['A♣,K♣,J♣,9♣,8♣', 'A♣,Q♣,J♣,9♣,8♣'],
        'flush: last card decides' => ['A♣,K♣,Q♣,J♣,9♣', 'A♣,K♣,Q♣,J♣,8♣'],
        'full house: higher trips wins' => ['K♣,K♦,K♥,A♠,A♣', 'Q♣,Q♦,Q♥,A♠,A♣'],
        'full house: same trips, higher pair wins' => ['A♣,A♦,A♥,K♠,K♣', 'A♣,A♦,A♥,Q♠,Q♣'],
        'four of a kind: higher quads wins' => ['K♣,K♦,K♥,K♠,A♣', 'Q♣,Q♦,Q♥,Q♠,A♣'],
        'four of a kind: same quads, kicker decides' => ['A♣,A♦,A♥,A♠,K♣', 'A♣,A♦,A♥,A♠,Q♣'],
        'straight flush: higher wins' => ['9♠,8♠,7♠,6♠,5♠', '6♠,5♠,4♠,3♠,2♠'],
        'straight flush: wheel is lowest' => ['2♠,3♠,4♠,5♠,6♠', 'A♣,2♣,3♣,4♣,5♣'],
    ];
});

/**
 * [cards_a, cards_b, label] — both hands should tie.
 */
dataset('hand comparison ties', function () {
    return [
        'high card exact tie' => ['A♣,K♦,Q♥,J♠,9♣', 'A♠,K♥,Q♦,J♣,9♥'],
        'pair exact tie' => ['A♣,A♦,K♥,Q♠,J♣', 'A♠,A♥,K♦,Q♣,J♦'],
        'two pair exact tie' => ['A♣,A♦,K♥,K♠,Q♣', 'A♠,A♥,K♦,K♣,Q♦'],
        'trips exact tie' => ['A♣,A♦,A♥,K♠,Q♣', 'A♠,A♥,A♦,K♣,Q♦'],
        'full house exact tie' => ['A♣,A♦,A♥,K♠,K♣', 'A♠,A♥,A♦,K♦,K♣'],
        'four of a kind exact tie' => ['A♣,A♦,A♥,A♠,K♣', 'A♣,A♦,A♥,A♠,K♥'],
        'straight exact tie' => ['6♣,7♦,8♥,9♠,10♣', '6♠,7♥,8♦,9♣,10♥'],
        'flush exact tie' => ['A♣,K♣,Q♣,J♣,9♣', 'A♠,K♠,Q♠,J♠,9♠'],
        'straight flush exact tie' => ['5♠,6♠,7♠,8♠,9♠', '5♣,6♣,7♣,8♣,9♣'],
        'royal flush always ties' => ['10♠,J♠,Q♠,K♠,A♠', '10♣,J♣,Q♣,K♣,A♣'],
        'wheel straight tie' => ['A♣,2♦,3♥,4♠,5♣', 'A♠,2♥,3♦,4♣,5♥'],
    ];
});

it('declares the winner correctly', function (string $winnerCards, string $loserCards) {
    $winner = pokerHandFromStringForCompare($winnerCards);
    $loser = pokerHandFromStringForCompare($loserCards);

    expect(HandRank::compare($winner, $loser))->toBe(1)
        ->and(HandRank::compare($loser, $winner))->toBe(-1);
})->with('hand comparison winner beats loser');

it('declares ties correctly', function (string $cardsA, string $cardsB) {
    $a = pokerHandFromStringForCompare($cardsA);
    $b = pokerHandFromStringForCompare($cardsB);

    expect(HandRank::compare($a, $b))->toBe(0)
        ->and(HandRank::compare($b, $a))->toBe(0);
})->with('hand comparison ties');

it('compare is reflexive', function (string $cards) {
    $hand = pokerHandFromStringForCompare($cards);
    expect(HandRank::compare($hand, $hand))->toBe(0);
})->with('poker hands and ranks');

it('royal flush is the highest rank and ties with any other royal flush', function () {
    $spades = pokerHandFromStringForCompare('10♠,J♠,Q♠,K♠,A♠');
    $hearts = pokerHandFromStringForCompare('10♥,J♥,Q♥,K♥,A♥');
    $fourAces = pokerHandFromStringForCompare('A♣,A♦,A♥,A♠,K♣');

    expect(HandRank::compare($spades, $hearts))->toBe(0)
        ->and(HandRank::compare($spades, $fourAces))->toBe(1);
});

it('wheel straight uses 5 as high card for comparison', function () {
    // Wheel (A-2-3-4-5) should lose to a 6-high straight (2-3-4-5-6)
    $wheel = pokerHandFromStringForCompare('A♣,2♦,3♥,4♠,5♣');
    $sixHigh = pokerHandFromStringForCompare('2♣,3♦,4♥,5♠,6♣');

    expect($wheel->getHighCardValue())->toBe(5)
        ->and($sixHigh->getHighCardValue())->toBe(6)
        ->and(HandRank::compare($wheel, $sixHigh))->toBe(-1);
});
