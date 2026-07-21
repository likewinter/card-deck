<?php

use Likewinter\CardDeck\Card\Suit;
use Likewinter\CardDeck\Trump;

it('has None, Suit, and NoTrump cases', function () {
    expect(Trump::cases())->toHaveCount(3)
        ->and(Trump::None)->toBeInstanceOf(Trump::class)
        ->and(Trump::Suit)->toBeInstanceOf(Trump::class)
        ->and(Trump::NoTrump)->toBeInstanceOf(Trump::class);
});

it('reports hasTrump correctly', function (Trump $trump, bool $hasTrump) {
    expect($trump->hasTrump())->toBe($hasTrump);
})->with([
    'None'    => [Trump::None, false],
    'Suit'    => [Trump::Suit, true],
    'NoTrump' => [Trump::NoTrump, false],
]);

it('only Suit is a suit trump', function () {
    expect(Trump::Suit->isSuit())->toBeTrue()
        ->and(Trump::None->isSuit())->toBeFalse()
        ->and(Trump::NoTrump->isSuit())->toBeFalse();
});
