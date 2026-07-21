<?php

use Likewinter\CardDeck\Trump;

it('has Suit and NoTrump cases', function () {
    expect(Trump::cases())->toHaveCount(2)
        ->and(Trump::Suit)->toBeInstanceOf(Trump::class)
        ->and(Trump::NoTrump)->toBeInstanceOf(Trump::class);
});

it('reports hasTrump correctly', function (Trump $trump, bool $hasTrump) {
    expect($trump->hasTrump())->toBe($hasTrump);
})->with([
    'Suit'    => [Trump::Suit, true],
    'NoTrump' => [Trump::NoTrump, false],
]);

it('only Suit has trump', function () {
    expect(Trump::Suit->hasTrump())->toBeTrue()
        ->and(Trump::NoTrump->hasTrump())->toBeFalse();
});
