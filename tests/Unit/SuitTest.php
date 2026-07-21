<?php

use Likewinter\CardDeck\Card\Suit;

it('has 5 cases including Joker', function () {
    expect(Suit::cases())->toHaveCount(5);
});

it('returns 4 cases without Joker', function () {
    $suits = Suit::casesWithoutJoker();
    expect($suits)->toHaveCount(4)
        ->and($suits)->not->toContain(Suit::Joker);
});

it('round-trips through symbol', function (Suit $suit) {
    expect(Suit::fromSymbol($suit->getSymbol()))->toBe($suit);
})->with([
    'Hearts' => [Suit::Hearts],
    'Diamonds' => [Suit::Diamonds],
    'Clubs' => [Suit::Clubs],
    'Spades' => [Suit::Spades],
    'Joker' => [Suit::Joker],
]);

it('returns correct symbols', function () {
    expect(Suit::Hearts->getSymbol())->toBe('♥')
        ->and(Suit::Diamonds->getSymbol())->toBe('♦')
        ->and(Suit::Clubs->getSymbol())->toBe('♣')
        ->and(Suit::Spades->getSymbol())->toBe('♠')
        ->and(Suit::Joker->getSymbol())->toBe('🃏');
});

it('returns correct colors', function () {
    expect(Suit::Hearts->getColor())->toBe('red')
        ->and(Suit::Diamonds->getColor())->toBe('red')
        ->and(Suit::Clubs->getColor())->toBe('black')
        ->and(Suit::Spades->getColor())->toBe('black');
});

it('rejects invalid symbol', function () {
    Suit::fromSymbol('X');
})->throws(\InvalidArgumentException::class);
