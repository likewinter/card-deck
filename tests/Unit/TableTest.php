<?php

use Likewinter\CardDeck\DeckBuilder;
use Likewinter\CardDeck\DrawMode;
use Likewinter\CardDeck\Stack;
use Likewinter\CardDeck\Table;

it('can be created with a deck', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    expect($table)->toBeInstanceOf(Table::class)
        ->and($table->deckCount())->toBe(52)
        ->and($table->pileCount())->toBe(0)
        ->and($table->handNames())->toBeEmpty();
});

it('does not shuffle the deck by default', function () {
    $deck = DeckBuilder::standard52()->build();
    $originalFirst = (string) $deck->peek(1);

    new Table(deck: $deck);

    expect((string) $deck->peek(1))->toBe($originalFirst);
});

it('shuffles the deck when shuffle: true', function () {
    $deck = DeckBuilder::standard52()->build();
    $originalOrder = (string) $deck;

    new Table(deck: $deck, shuffle: true);

    expect((string) $deck)->not->toBe($originalOrder);
});

// ── Hand registry ────────────────────────────────────────────────────────

it('can add and retrieve hands by name', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $hand = new Stack(capacity: 5);
    $table->addHand('player-0', $hand);

    expect($table->hand('player-0'))->toBe($hand)
        ->and($table->hasHand('player-0'))->toBeTrue()
        ->and($table->handNames())->toBe(['player-0']);
});

it('rejects duplicate hand names', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('player-0', new Stack());
    $table->addHand('player-0', new Stack());
})->throws(\InvalidArgumentException::class);

it('throws when accessing a non-existent hand', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->hand('ghost');
})->throws(\InvalidArgumentException::class);

it('can remove a hand and dump its cards onto the pile', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('player-0', new Stack(capacity: 5));
    $table->addHand('player-1', new Stack(capacity: 5));
    $table->draw('player-0', 3);

    $table->removeHand('player-0');

    expect($table->hasHand('player-0'))->toBeFalse()
        ->and($table->hasHand('player-1'))->toBeTrue()
        ->and($table->pileCount())->toBe(3);
});

// ── Drawing ──────────────────────────────────────────────────────────────

it('draws sequentially by default', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build(), drawMode: DrawMode::Sequential);
    $table->addHand('p0', new Stack(capacity: 5));
    $table->addHand('p1', new Stack(capacity: 5));
    $table->drawAll(3);

    expect($table->hand('p0')->count())->toBe(3)
        ->and($table->hand('p1')->count())->toBe(3)
        ->and($table->deckCount())->toBe(52 - 6);
});

it('draws one card per hand per round in OneByOne mode', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build(), drawMode: DrawMode::OneByOne);
    $table->addHand('p0', new Stack(capacity: 5));
    $table->addHand('p1', new Stack(capacity: 5));
    $table->drawAll(2);

    expect($table->hand('p0')->count())->toBe(2)
        ->and($table->hand('p1')->count())->toBe(2)
        ->and($table->deckCount())->toBe(52 - 4);
});

it('draws random cards in Random mode', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build(), drawMode: DrawMode::Random);
    $table->addHand('p0', new Stack(capacity: 5));
    $table->addHand('p1', new Stack(capacity: 5));
    $table->drawAll(2);

    expect($table->hand('p0')->count())->toBe(2)
        ->and($table->hand('p1')->count())->toBe(2)
        ->and($table->deckCount())->toBe(52 - 4);
});

it('draws to a specific hand', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('p0', new Stack(capacity: 5));
    $table->draw('p0', 4);

    expect($table->hand('p0')->count())->toBe(4)
        ->and($table->deckCount())->toBe(52 - 4);
});

it('draw rejects a non-existent hand', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->draw('ghost', 1);
})->throws(\InvalidArgumentException::class);

it('drawAll throws when there are no hands', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->drawAll(1);
})->throws(\LogicException::class);

it('drawAll throws when the deck does not have enough cards', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('p0', new Stack());
    $table->addHand('p1', new Stack());
    $table->drawAll(27);
})->throws(\LogicException::class);

it('draw throws when the deck does not have enough cards', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('p0', new Stack());
    $table->draw('p0', 53);
})->throws(\LogicException::class);

// ── Discarding ───────────────────────────────────────────────────────────

it('discards the whole hand when no cards are specified', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('p0', new Stack(capacity: 5));
    $table->draw('p0', 3);

    $table->discard('p0');

    expect($table->hand('p0')->count())->toBe(0)
        ->and($table->pileCount())->toBe(3);
});

it('discards only the specified cards when provided', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('p0', new Stack(capacity: 5));
    $table->draw('p0', 4);
    $cards = [...$table->hand('p0')];
    $cardsToDiscard = array_slice($cards, 0, 2);

    $table->discard('p0', ...$cardsToDiscard);

    expect($table->hand('p0')->count())->toBe(2)
        ->and($table->pileCount())->toBe(2);
});

it('collects external cards into the pile', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $cards = [...DeckBuilder::standard52()->build()->takeTop(3)];

    $table->collectToPile(...$cards);

    expect($table->pileCount())->toBe(3);
});

// ── Lifecycle ────────────────────────────────────────────────────────────

it('reset returns all cards to the deck', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build(), shuffle: true);
    $table->addHand('p0', new Stack(capacity: 5));
    $table->draw('p0', 5);
    $table->discard('p0');

    expect($table->deckCount())->toBe(47)
        ->and($table->pileCount())->toBe(5);

    $table->reset();

    expect($table->deckCount())->toBe(52)
        ->and($table->pileCount())->toBe(0)
        ->and($table->hand('p0')->count())->toBe(0);
});

it('reset recovers collected pile cards', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('p0', new Stack());
    $table->draw('p0', 5);

    $playedCards = [...$table->hand('p0')->takeTop(3)];
    $table->collectToPile(...$playedCards);

    expect($table->deckCount())->toBe(47)
        ->and($table->pileCount())->toBe(3)
        ->and($table->hand('p0')->count())->toBe(2);

    $table->reset();

    expect($table->deckCount())->toBe(52)
        ->and($table->pileCount())->toBe(0)
        ->and($table->hand('p0')->count())->toBe(0);
});

it('shuffle randomizes the deck', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('p0', new Stack());
    $table->draw('p0', 52);
    $originalOrder = (string) $table->hand('p0');
    $table->reset();

    $table->shuffle();
    $table->draw('p0', 52);

    expect((string) $table->hand('p0'))->not->toBe($originalOrder);
});

// ── drawUpTo ────────────────────────────────────────────────────────────

it('drawUpTo fills a hand to the target count', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('p0', new Stack());
    $table->draw('p0', 3);

    $drawn = $table->drawUpTo('p0', 6);

    expect($drawn)->toBe(3)
        ->and($table->hand('p0')->count())->toBe(6)
        ->and($table->deckCount())->toBe(52 - 6);
});

it('drawUpTo draws nothing when hand already meets target', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());
    $table->addHand('p0', new Stack());
    $table->draw('p0', 6);

    $drawn = $table->drawUpTo('p0', 6);

    expect($drawn)->toBe(0)
        ->and($table->hand('p0')->count())->toBe(6);
});

it('drawUpTo draws only what the deck has when it runs short', function () {
    $deck = DeckBuilder::standard52()->build();
    $table = new Table(deck: $deck);
    $table->addHand('p0', new Stack());
    $table->draw('p0', 50);

    $drawn = $table->drawUpTo('p0', 55);

    expect($drawn)->toBe(2)
        ->and($table->hand('p0')->count())->toBe(52)
        ->and($table->deckCount())->toBe(0);
});

it('drawUpTo returns 0 on an empty deck', function () {
    $table = new Table(deck: new Stack());
    $table->addHand('p0', new Stack());

    $drawn = $table->drawUpTo('p0', 6);

    expect($drawn)->toBe(0)
        ->and($table->hand('p0')->count())->toBe(0);
});

// ── peekDeck ────────────────────────────────────────────────────────────

it('peekDeck shows cards without removing them', function () {
    $table = new Table(deck: DeckBuilder::standard52()->build());

    $top = $table->peekDeck(1);
    $bottom = $table->peekDeck(1, fromTop: false);

    expect($top->count())->toBe(1)
        ->and($bottom->count())->toBe(1)
        ->and($table->deckCount())->toBe(52);
});
