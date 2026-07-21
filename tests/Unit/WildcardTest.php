<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};
use Likewinter\CardDeck\Wildcard;

it('wraps a joker with no assignment by default', function () {
    $joker = new Card(Suit::Joker, Rank::Joker);
    $wild = new Wildcard($joker);

    expect($wild->wild)->toBe($joker)
        ->and($wild->assigned)->toBeNull()
        ->and($wild->isUnassigned())->toBeTrue()
        ->and($wild->isAssigned())->toBeFalse();
});

it('assign() returns a new instance with the substitution', function () {
    $joker = new Card(Suit::Joker, Rank::Joker);
    $kingOfHearts = new Card(Suit::Hearts, Rank::King);

    $assigned = (new Wildcard($joker))->assign($kingOfHearts);

    expect($assigned->assigned)->toBe($kingOfHearts)
        ->and($assigned->effective())->toBe($kingOfHearts)
        ->and($assigned->isAssigned())->toBeTrue();
});

it('unassign() returns a new instance with no substitution', function () {
    $joker = new Card(Suit::Joker, Rank::Joker);
    $king = new Card(Suit::Hearts, Rank::King);

    $assigned = (new Wildcard($joker))->assign($king);
    $unassigned = $assigned->unassign();

    expect($unassigned->assigned)->toBeNull()
        ->and($unassigned->isUnassigned())->toBeTrue()
        ->and($unassigned->wild)->toBe($joker); // original wild card preserved
});

it('does not mutate the original on assign', function () {
    $joker = new Card(Suit::Joker, Rank::Joker);
    $original = new Wildcard($joker);
    $king = new Card(Suit::Hearts, Rank::King);

    $assigned = $original->assign($king);

    expect($original->isUnassigned())->toBeTrue() // unchanged
        ->and($assigned->isAssigned())->toBeTrue();
});

it('effective() returns the assigned card or null', function () {
    $joker = new Card(Suit::Joker, Rank::Joker);
    $king = new Card(Suit::Hearts, Rank::King);

    $unassigned = new Wildcard($joker);
    $assigned = $unassigned->assign($king);

    expect($unassigned->effective())->toBeNull()
        ->and($assigned->effective())->toBe($king);
});

it('string representation shows the assigned card when assigned', function () {
    $joker = new Card(Suit::Joker, Rank::Joker);
    $king = new Card(Suit::Hearts, Rank::King);

    expect((string) new Wildcard($joker))->toBe('🃏🃏')
        ->and((string) (new Wildcard($joker))->assign($king))->toBe('K♥');
});

it('allows non-joker wildcards (e.g. Crazy Eights 8s)', function () {
    $eight = new Card(Suit::Hearts, Rank::Eight);
    $wild = new Wildcard($eight);

    expect($wild->wild)->toBe($eight)
        ->and($wild->isUnassigned())->toBeTrue();
});
