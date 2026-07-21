<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};
use Likewinter\CardDeck\CardInPlay;
use Likewinter\CardDeck\Face;

it('has Up and Down cases', function () {
    expect(Face::cases())->toHaveCount(2)
        ->and(Face::Up->value)->toBe('up')
        ->and(Face::Down->value)->toBe('down');
});

it('isUp and isDown report state', function () {
    expect(Face::Up->isUp())->toBeTrue()
        ->and(Face::Up->isDown())->toBeFalse()
        ->and(Face::Down->isUp())->toBeFalse()
        ->and(Face::Down->isDown())->toBeTrue();
});

it('defaults to face-up', function () {
    $card = new Card(Suit::Hearts, Rank::Ace);
    $inPlay = new CardInPlay($card);
    expect($inPlay->face)->toBe(Face::Up)
        ->and($inPlay->isFaceUp())->toBeTrue()
        ->and($inPlay->isFaceDown())->toBeFalse();
});

it('up() and down() factories set face state', function () {
    $card = new Card(Suit::Hearts, Rank::Ace);
    expect(CardInPlay::up($card)->isFaceUp())->toBeTrue()
        ->and(CardInPlay::down($card)->isFaceDown())->toBeTrue();
});

it('flip() toggles face and returns a new instance', function () {
    $card = new Card(Suit::Hearts, Rank::Ace);
    $up = CardInPlay::up($card);
    $down = $up->flip();

    expect($up->isFaceUp())->toBeTrue()      // original unchanged
        ->and($down->isFaceDown())->toBeTrue()
        ->and($down)->not->toBe($up)
        ->and($down->card)->toBe($card);     // underlying card shared
});

it('reveal() and hide() are idempotent', function () {
    $card = new Card(Suit::Hearts, Rank::Ace);

    $up = CardInPlay::up($card);
    expect($up->reveal())->toBe($up)         // no-op, returns same instance
        ->and($up->hide()->isFaceDown())->toBeTrue();

    $down = CardInPlay::down($card);
    expect($down->hide())->toBe($down)       // no-op, returns same instance
        ->and($down->reveal()->isFaceUp())->toBeTrue();
});

it('string representation hides face-down cards', function () {
    $card = new Card(Suit::Hearts, Rank::Ace);
    expect((string) CardInPlay::up($card))->toBe('A♥')
        ->and((string) CardInPlay::down($card))->toBe('██');
});

it('preserves the underlying card identity', function () {
    $card = new Card(Suit::Hearts, Rank::Ace);
    $inPlay = CardInPlay::down($card);
    expect($inPlay->card)->toBe($card)
        ->and($inPlay->card->rank)->toBe(Rank::Ace)
        ->and($inPlay->card->suit)->toBe(Suit::Hearts);
});
