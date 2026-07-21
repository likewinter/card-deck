<?php

use Likewinter\CardDeck\Dealer;
use Likewinter\CardDeck\Deck;
use Likewinter\CardDeck\Exceptions\DealerException;
use Likewinter\CardDeck\Games\Poker\PokerDeck;
use Likewinter\CardDeck\Hand;

it('can be created with a deck', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    expect($dealer)->toBeInstanceOf(Dealer::class)
        ->and($dealer->getDeck())->toBeInstanceOf(Deck::class)
        ->and($dealer->getHands())->toBeEmpty()
        ->and($dealer->getPile()->count())->toBe(0);
});

it('rejects invalid draw mode', function () {
    // @phpstan-ignore-next-line
    new Dealer(deck: new PokerDeck(), drawMode: 99);
})->throws(DealerException::class);

it('rejects non-Hand entries in constructor hands array', function () {
    // @phpstan-ignore-next-line
    new Dealer(deck: new PokerDeck(), hands: ['not a hand']);
})->throws(DealerException::class);

it('can add and list hands', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $hand = new Hand(handSize: 5);
    $dealer->addHands($hand);
    expect($dealer->getHands())->toHaveCount(1)
        ->and($dealer->getHands()[0])->toBe($hand);
});

it('can remove a hand and dump its cards onto the pile', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $hand1 = new Hand(handSize: 5);
    $hand2 = new Hand(handSize: 5);
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
    $dealer = new Dealer(deck: new PokerDeck());
    $hand1 = new Hand(handSize: 5);
    $hand2 = new Hand(handSize: 5);
    $dealer->addHands($hand1, $hand2);

    $dealer->removeHands($hand1);

    expect($dealer->getHands())->toHaveCount(1)
        ->and($dealer->getHands()[0])->toBe($hand2);
});

it('rejects removing a hand that was not added', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $dealer->addHands(new Hand(handSize: 5));
    $dealer->removeHands(new Hand(handSize: 5));
})->throws(DealerException::class);

it('draws sequentially by default', function () {
    $dealer = new Dealer(deck: new PokerDeck(), drawMode: Dealer::DRAW_SEQUENTIAL);
    $hand1 = new Hand(handSize: 5);
    $hand2 = new Hand(handSize: 5);
    $dealer->addHands($hand1, $hand2);
    $dealer->drawAll(3);

    expect($hand1->count())->toBe(3)
        ->and($hand2->count())->toBe(3)
        ->and($dealer->getDeck()->count())->toBe(52 - 6);
});

it('draws one card per hand per round in DRAW_ONE_BY_ONE mode', function () {
    $dealer = new Dealer(deck: new PokerDeck(), drawMode: Dealer::DRAW_ONE_BY_ONE);
    $hand1 = new Hand(handSize: 5);
    $hand2 = new Hand(handSize: 5);
    $dealer->addHands($hand1, $hand2);
    $dealer->drawAll(2);

    expect($hand1->count())->toBe(2)
        ->and($hand2->count())->toBe(2)
        ->and($dealer->getDeck()->count())->toBe(52 - 4);
});

it('draws random cards in DRAW_RANDOM mode', function () {
    $dealer = new Dealer(deck: new PokerDeck(), drawMode: Dealer::DRAW_RANDOM);
    $hand1 = new Hand(handSize: 5);
    $hand2 = new Hand(handSize: 5);
    $dealer->addHands($hand1, $hand2);
    $dealer->drawAll(2);

    expect($hand1->count())->toBe(2)
        ->and($hand2->count())->toBe(2)
        ->and($dealer->getDeck()->count())->toBe(52 - 4);
});

it('drawToHand draws to a specific hand', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $hand = new Hand(handSize: 5);
    $dealer->addHands($hand);
    $dealer->drawToHand($hand, 4);

    expect($hand->count())->toBe(4)
        ->and($dealer->getDeck()->count())->toBe(52 - 4);
});

it('drawToHand rejects a hand not managed by the dealer', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $dealer->drawToHand(new Hand(handSize: 5), 1);
})->throws(DealerException::class);

it('drawAll throws when there are no hands', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $dealer->drawAll(1);
})->throws(DealerException::class);

it('drawAll throws when the deck does not have enough cards', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $dealer->addHands(new Hand(handSize: 5), new Hand(handSize: 5));
    // 52 cards, 2 hands, request 27 each = 54 needed > 52 available
    $dealer->drawAll(27);
})->throws(DealerException::class);

it('discards the whole hand when no cards are specified', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $hand = new Hand(handSize: 5);
    $dealer->addHands($hand);
    $dealer->drawToHand($hand, 3);

    $dealer->discard($hand);

    expect($hand->count())->toBe(0)
        ->and($dealer->getPile()->count())->toBe(3);
});

it('discards only the specified cards when provided', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $hand = new Hand(handSize: 5);
    $dealer->addHands($hand);
    $dealer->drawToHand($hand, 4);
    $cards = [...$hand];
    $cardsToDiscard = array_slice($cards, 0, 2);

    $dealer->discard($hand, ...$cardsToDiscard);

    expect($hand->count())->toBe(2)
        ->and($dealer->getPile()->count())->toBe(2);
});

it('resetGame returns all cards to the deck', function () {
    $dealer = new Dealer(deck: new PokerDeck());
    $hand = new Hand(handSize: 5);
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
