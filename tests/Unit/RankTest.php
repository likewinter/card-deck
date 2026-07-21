<?php

use Likewinter\CardDeck\Card\Rank;

it('has 14 cases including Joker', function () {
    expect(Rank::cases())->toHaveCount(14);
});

it('returns 13 cases without Joker', function () {
    $ranks = Rank::casesWithoutJoker();
    expect($ranks)->toHaveCount(13)
        ->and($ranks)->not->toContain(Rank::Joker);
});

it('round-trips through symbol', function (Rank $rank) {
    expect(Rank::fromSymbol($rank->getSymbol()))->toBe($rank);
})->with([
    'Two' => [Rank::Two],
    'Ten' => [Rank::Ten],
    'Jack' => [Rank::Jack],
    'Queen' => [Rank::Queen],
    'King' => [Rank::King],
    'Ace' => [Rank::Ace],
    'Joker' => [Rank::Joker],
]);

it('returns correct symbols', function () {
    expect(Rank::Two->getSymbol())->toBe('2')
        ->and(Rank::Ten->getSymbol())->toBe('10')
        ->and(Rank::Jack->getSymbol())->toBe('J')
        ->and(Rank::Ace->getSymbol())->toBe('A')
        ->and(Rank::Joker->getSymbol())->toBe('🃏');
});

it('rejects invalid symbol', function () {
    Rank::fromSymbol('X');
})->throws(\InvalidArgumentException::class);
