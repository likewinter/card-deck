<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Games\Poker\HandRank;
use Likewinter\CardDeck\Games\Poker\PokerHand;

function pokerHandFromString(string $cards): PokerHand
{
    $cardsArr = array_map(
        fn (string $c) => Card::fromString(trim($c)),
        explode(',', $cards)
    );

    return new PokerHand(cards: $cardsArr);
}

it('ranks poker hands correctly', function (string $cards, HandRank $expectedRank) {
    $hand = pokerHandFromString($cards);
    expect($hand->handRank)->toBe($expectedRank);
})->with('poker hands and ranks');

it('detects every straight sequence', function (string $cards) {
    $hand = pokerHandFromString($cards);
    expect($hand->isSequentialRank)->toBeTrue()
        ->and($hand->handRank)->toBe(HandRank::STRAIGHT);
})->with([
    'wheel A-2-3-4-5' => ['A♣,2♦,3♥,4♠,5♣'],
    '2-3-4-5-6' => ['2♣,3♦,4♥,5♠,6♣'],
    '3-4-5-6-7' => ['3♣,4♦,5♥,6♠,7♣'],
    '4-5-6-7-8' => ['4♣,5♦,6♥,7♠,8♣'],
    '5-6-7-8-9' => ['5♣,6♦,7♥,8♠,9♣'],
    '6-7-8-9-10' => ['6♣,7♦,8♥,9♠,10♣'],
    '7-8-9-10-J' => ['7♣,8♦,9♥,10♠,J♣'],
    '8-9-10-J-Q' => ['8♣,9♦,10♥,J♠,Q♣'],
    '9-10-J-Q-K' => ['9♣,10♦,J♥,Q♠,K♣'],
    'broadway 10-J-Q-K-A' => ['10♣,J♦,Q♥,K♠,A♣'],
]);

it('detects wheel and broadway straight flushes', function (string $cards) {
    $hand = pokerHandFromString($cards);
    expect($hand->handRank)->toBe(HandRank::STRAIGHT_FLUSH)
        ->and($hand->isSequentialRank)->toBeTrue()
        ->and($hand->isSameSuit)->toBeTrue();
})->with([
    'wheel straight flush' => ['A♣,2♣,3♣,4♣,5♣'],
    'broadway straight flush' => ['10♠,J♠,Q♠,K♠,A♠'],
]);

it('does not report a straight for non-sequential ranks', function () {
    $hand = pokerHandFromString('A♣,K♦,Q♥,J♠,9♣');
    expect($hand->isSequentialRank)->toBeFalse();
});
