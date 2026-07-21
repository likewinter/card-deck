<?php

use Likewinter\CardDeck\Card\Rank;
use Likewinter\CardDeck\RankOrder;

describe('poker ordering', function () {
    it('assigns ascending values from 2 to Ace', function () {
        $order = RankOrder::poker();
        expect($order->value(Rank::Two))->toBe(2)
            ->and($order->value(Rank::Ten))->toBe(10)
            ->and($order->value(Rank::King))->toBe(13)
            ->and($order->value(Rank::Ace))->toBe(14);
    });

    it('treats Ace as the highest rank', function () {
        $order = RankOrder::poker();
        expect($order->highest)->toBe(Rank::Ace)
            ->and($order->isHighest(Rank::Ace))->toBeTrue()
            ->and($order->isHighest(Rank::King))->toBeFalse();
    });

    it('compares ranks correctly', function () {
        $order = RankOrder::poker();
        expect($order->compare(Rank::Jack, Rank::Ten))->toBe(1)
            ->and($order->compare(Rank::Ten, Rank::Jack))->toBe(-1)
            ->and($order->compare(Rank::Jack, Rank::Jack))->toBe(0)
            ->and($order->isHigher(Rank::Jack, Rank::Ten))->toBeTrue()
            ->and($order->isHigher(Rank::Ten, Rank::Jack))->toBeFalse();
    });

    it('walks next and previous', function () {
        $order = RankOrder::poker();
        expect($order->next(Rank::Ten))->toBe(Rank::Jack)
            ->and($order->next(Rank::Ace))->toBeNull()
            ->and($order->previous(Rank::Two))->toBeNull()
            ->and($order->previous(Rank::Ace))->toBe(Rank::King);
    });
});

describe('pokerLowAce ordering', function () {
    it('treats Ace as 1', function () {
        $order = RankOrder::pokerLowAce();
        expect($order->value(Rank::Ace))->toBe(1)
            ->and($order->value(Rank::Two))->toBe(2)
            ->and($order->highest)->toBe(Rank::King);
    });

    it('Ace is the lowest in this ordering', function () {
        $order = RankOrder::pokerLowAce();
        expect($order->isHigher(Rank::Two, Rank::Ace))->toBeTrue()
            ->and($order->previous(Rank::Ace))->toBeNull();
    });
});

describe('blackjack ordering', function () {
    it('values face cards as 10', function () {
        $order = RankOrder::blackjack();
        expect($order->value(Rank::Jack))->toBe(10)
            ->and($order->value(Rank::Queen))->toBe(10)
            ->and($order->value(Rank::King))->toBe(10);
    });

    it('values Ace as 11 (soft default)', function () {
        $order = RankOrder::blackjack();
        expect($order->value(Rank::Ace))->toBe(11)
            ->and($order->value(Rank::Ten))->toBe(10);
    });
});

it('throws when asking for value of a rank not in the order', function () {
    // Joker is not in poker() — should throw
    RankOrder::poker()->value(Rank::Joker);
})->throws(\InvalidArgumentException::class);
