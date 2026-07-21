<?php

use Likewinter\CardDeck\Dealer;
use Likewinter\CardDeck\DeckBuilder;
use Likewinter\CardDeck\DrawMode;
use Likewinter\CardDeck\Exceptions\DealerException;
use Likewinter\CardDeck\Hand;
use Likewinter\CardDeck\Stack;

it('can be created with a deck', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    expect($dealer)->toBeInstanceOf(Dealer::class)
        ->and($dealer->getDeck())->toBeInstanceOf(Stack::class)
        ->and($dealer->getHands())->toBeEmpty()
        ->and($dealer->getPile()->count())->toBe(0);
});

it('rejects non-Hand entries in constructor hands array', function () {
    // @phpstan-ignore-next-line
    new Dealer(deck: DeckBuilder::standard52()->build(), hands: ['not a hand']);
})->throws(DealerException::class);

it('can add and list hands', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $hand = new Hand(capacity: 5);
    $dealer->addHands($hand);
    expect($dealer->getHands())->toHaveCount(1)
        ->and($dealer->getHands()[0])->toBe($hand);
});

it('can remove a hand and dump its cards onto the pile', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $hand1 = new Hand(capacity: 5);
    $hand2 = new Hand(capacity: 5);
    $dealer->addHands($hand1, $hand2);
    $dealer->drawToHand($hand1, 3);

    $dealer->removeHands($hand1);

    expect($dealer->getHands())->toHaveCount(1)
        ->and($dealer->getHands()[0])->toBe($hand2)
        ->and($hand1->count())->toBe(0)
        ->and($dealer->getPile()->count())->toBe(3);
});

it('removes only the targeted hand when two empty hands exist', function () {
    // Regression: previously array_diff compared by string, so two empty
    // hands (both stringify to "") were both removed.
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $hand1 = new Hand(capacity: 5);
    $hand2 = new Hand(capacity: 5);
    $dealer->addHands($hand1, $hand2);

    $dealer->removeHands($hand1);

    expect($dealer->getHands())->toHaveCount(1)
        ->and($dealer->getHands()[0])->toBe($hand2);
});

it('rejects removing a hand that was not added', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $dealer->addHands(new Hand(capacity: 5));
    $dealer->removeHands(new Hand(capacity: 5));
})->throws(DealerException::class);

it('draws sequentially by default', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build(), drawMode: DrawMode::Sequential);
    $hand1 = new Hand(capacity: 5);
    $hand2 = new Hand(capacity: 5);
    $dealer->addHands($hand1, $hand2);
    $dealer->drawAll(3);

    expect($hand1->count())->toBe(3)
        ->and($hand2->count())->toBe(3)
        ->and($dealer->getDeck()->count())->toBe(52 - 6);
});

it('draws one card per hand per round in DRAW_ONE_BY_ONE mode', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build(), drawMode: DrawMode::OneByOne);
    $hand1 = new Hand(capacity: 5);
    $hand2 = new Hand(capacity: 5);
    $dealer->addHands($hand1, $hand2);
    $dealer->drawAll(2);

    expect($hand1->count())->toBe(2)
        ->and($hand2->count())->toBe(2)
        ->and($dealer->getDeck()->count())->toBe(52 - 4);
});

it('draws random cards in DRAW_RANDOM mode', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build(), drawMode: DrawMode::Random);
    $hand1 = new Hand(capacity: 5);
    $hand2 = new Hand(capacity: 5);
    $dealer->addHands($hand1, $hand2);
    $dealer->drawAll(2);

    expect($hand1->count())->toBe(2)
        ->and($hand2->count())->toBe(2)
        ->and($dealer->getDeck()->count())->toBe(52 - 4);
});

it('drawToHand draws to a specific hand', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $hand = new Hand(capacity: 5);
    $dealer->addHands($hand);
    $dealer->drawToHand($hand, 4);

    expect($hand->count())->toBe(4)
        ->and($dealer->getDeck()->count())->toBe(52 - 4);
});

it('drawToHand rejects a hand not managed by the dealer', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $dealer->drawToHand(new Hand(capacity: 5), 1);
})->throws(DealerException::class);

it('drawAll throws when there are no hands', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $dealer->drawAll(1);
})->throws(DealerException::class);

it('drawAll throws when the deck does not have enough cards', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $dealer->addHands(new Hand(capacity: 5), new Hand(capacity: 5));
    // 52 cards, 2 hands, request 27 each = 54 needed > 52 available
    $dealer->drawAll(27);
})->throws(DealerException::class);

it('discards the whole hand when no cards are specified', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $hand = new Hand(capacity: 5);
    $dealer->addHands($hand);
    $dealer->drawToHand($hand, 3);

    $dealer->discard($hand);

    expect($hand->count())->toBe(0)
        ->and($dealer->getPile()->count())->toBe(3);
});

it('discards only the specified cards when provided', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build());
    $hand = new Hand(capacity: 5);
    $dealer->addHands($hand);
    $dealer->drawToHand($hand, 4);
    $cards = [...$hand];
    $cardsToDiscard = array_slice($cards, 0, 2);

    $dealer->discard($hand, ...$cardsToDiscard);

    expect($hand->count())->toBe(2)
        ->and($dealer->getPile()->count())->toBe(2);
});

it('resetGame returns all cards to the deck', function () {
    $dealer = new Dealer(deck: DeckBuilder::standard52()->build(), shuffle: true);
    $hand = new Hand(capacity: 5);
    $dealer->addHands($hand);
    $dealer->drawToHand($hand, 5);
    $dealer->discard($hand);

    expect($dealer->getDeck()->count())->toBe(47)
        ->and($dealer->getPile()->count())->toBe(5);

    $dealer->resetGame();

    expect($dealer->getDeck()->count())->toBe(52)
        ->and($dealer->getPile()->count())->toBe(0)
        ->and($hand->count())->toBe(0);
});

it('does not shuffle the deck by default', function () {
    $deck = DeckBuilder::standard52()->build();
    $originalFirst = (string) $deck->peek(1);

    new Dealer(deck: $deck);

    // Deck should be unchanged — no shuffle
    expect((string) $deck->peek(1))->toBe($originalFirst);
});

it('shuffles the deck when shuffle: true', function () {
    $deck = DeckBuilder::standard52()->build();
    $originalOrder = (string) $deck;

    new Dealer(deck: $deck, shuffle: true);

    // Very unlikely a shuffle produces the same 52-card order
    expect((string) $deck)->not->toBe($originalOrder);
});

it('moveTo rolls back on target capacity exceeded', function () {
    // Source has 3 cards, target has capacity 2.
    // Moving 3 cards should fail AND leave the source intact.
    $source = Stack::fromString('A♣,2♦,3♥');
    $target = new Stack(capacity: 2);

    try {
        $source->moveTo($target, 3);
        expect(false)->toBeTrue('Expected exception was not thrown');
    } catch (\InvalidArgumentException $e) {
        // Expected — target can't hold 3 cards
    }

    // Source should still have all 3 cards (rolled back)
    expect($source->count())->toBe(3)
        ->and($target->count())->toBe(0);
});

it('moveAllTo rolls back on target capacity exceeded', function () {
    $source = Stack::fromString('A♣,2♦,3♥');
    $target = new Stack(capacity: 2);

    try {
        $source->moveAllTo($target);
        expect(false)->toBeTrue('Expected exception was not thrown');
    } catch (\InvalidArgumentException $e) {
        // Expected
    }

    expect($source->count())->toBe(3)
        ->and($target->count())->toBe(0);
});
