<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Games\Blackjack\BlackjackHand;
use Likewinter\CardDeck\Card\{Rank, Suit};

function bjHand(string $cards): BlackjackHand
{
    $cardsArr = array_map(
        fn(string $c) => Card::fromString(trim($c)),
        explode(',', $cards)
    );

    return new BlackjackHand($cardsArr);
}

it('values face cards at 10', function () {
    expect(bjHand('K♠,Q♥')->value())->toBe(20)
        ->and(bjHand('J♣,10♦')->value())->toBe(20);
});

it('values aces as 11 when safe', function () {
    expect(bjHand('A♠,K♥')->value())->toBe(21)
        ->and(bjHand('A♣,6♦')->value())->toBe(17);
});

it('reduces aces to 1 to avoid bust', function () {
    expect(bjHand('A♠,A♥,9♦')->value())->toBe(21)  // 11+1+9
        ->and(bjHand('A♠,A♥,A♦,8♣')->value())->toBe(21); // 11+1+1+8
});

it('detects bust', function () {
    expect(bjHand('K♠,Q♥,2♦')->isBust())->toBeTrue()
        ->and(bjHand('K♠,7♥')->isBust())->toBeFalse();
});

it('detects blackjack (2 cards totaling 21)', function () {
    expect(bjHand('A♠,K♥')->isBlackjack())->toBeTrue()
        ->and(bjHand('A♠,10♥')->isBlackjack())->toBeTrue()
        ->and(bjHand('A♠,K♥,2♦')->isBlackjack())->toBeFalse()  // 3 cards
        ->and(bjHand('K♠,Q♥,A♦')->isBlackjack())->toBeFalse(); // 3 cards
});

it('detects soft hands', function () {
    expect(bjHand('A♠,6♦')->isSoft())->toBeTrue()   // A=11, total 17
        ->and(bjHand('A♠,K♥')->isSoft())->toBeTrue() // A=11, total 21
        ->and(bjHand('K♠,7♥')->isSoft())->toBeFalse(); // no ace
});

it('counts cards', function () {
    expect(bjHand('A♠,K♥')->count())->toBe(2)
        ->and(bjHand('A♠,K♥,2♦')->count())->toBe(3);
});

it('iterates over cards', function () {
    $hand = bjHand('A♠,K♥');
    expect([...$hand])->toHaveCount(2);
});

it('renders as comma-separated string', function () {
    expect((string) bjHand('A♠,K♥'))->toBe('A♠,K♥');
});
