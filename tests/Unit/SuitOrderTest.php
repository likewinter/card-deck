<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\SuitOrder;
use Likewinter\CardDeck\Trump;

function cardOf(Suit $suit, Rank $rank): Card
{
    return new Card(suit: $suit, rank: $rank);
}

describe('construction', function () {
    it('creates no-trump via factory', function () {
        $so = SuitOrder::noTrump();
        expect($so->trump)->toBe(Trump::NoTrump)
            ->and($so->trumpSuit)->toBeNull();
    });

    it('creates suit trump via factory', function () {
        $so = SuitOrder::suit(Suit::Spades);
        expect($so->trump)->toBe(Trump::Suit)
            ->and($so->trumpSuit)->toBe(Suit::Spades);
    });

    it('rejects Trump::Suit without a trump suit', function () {
        new SuitOrder(Trump::Suit, null);
    })->throws(\InvalidArgumentException::class);

    it('rejects trump suit with non-Suit trump', function () {
        new SuitOrder(Trump::NoTrump, Suit::Spades);
    })->throws(\InvalidArgumentException::class);

    it('rejects Joker as trump suit', function () {
        new SuitOrder(Trump::Suit, Suit::Joker);
    })->throws(\InvalidArgumentException::class);
});

describe('isTrump', function () {
    it('identifies trump cards when Trump::Suit', function () {
        $so = SuitOrder::suit(Suit::Hearts);
        expect($so->isTrump(cardOf(Suit::Hearts, Rank::Two)))->toBeTrue()
            ->and($so->isTrump(cardOf(Suit::Spades, Rank::Ace)))->toBeFalse();
    });

    it('identifies no cards as trump when NoTrump', function () {
        $so = SuitOrder::noTrump();
        expect($so->isTrump(cardOf(Suit::Hearts, Rank::Two)))->toBeFalse();
    });
});

describe('beats — trump vs non-trump', function () {
    it('trump beats any non-trump regardless of rank', function () {
        $so = SuitOrder::suit(Suit::Spades);
        $order = RankOrder::poker();
        $twoOfTrump = cardOf(Suit::Spades, Rank::Two);
        $aceOfHearts = cardOf(Suit::Hearts, Rank::Ace);

        expect($so->beats($twoOfTrump, $aceOfHearts, Suit::Hearts, $order))->toBeTrue()
            ->and($so->beats($aceOfHearts, $twoOfTrump, Suit::Hearts, $order))->toBeFalse();
    });
});

describe('beats — both trump', function () {
    it('higher trump beats lower trump', function () {
        $so = SuitOrder::suit(Suit::Spades);
        $order = RankOrder::poker();

        expect($so->beats(
            cardOf(Suit::Spades, Rank::Ace),
            cardOf(Suit::Spades, Rank::Two),
            Suit::Spades,
            $order
        ))->toBeTrue();
    });
});

describe('beats — no trump', function () {
    it('higher card in led suit beats lower card in led suit', function () {
        $so = SuitOrder::noTrump();
        $order = RankOrder::poker();

        expect($so->beats(
            cardOf(Suit::Hearts, Rank::King),
            cardOf(Suit::Hearts, Rank::Ten),
            Suit::Hearts,
            $order
        ))->toBeTrue();
    });

    it('off-suit card does not beat led-suit card', function () {
        $so = SuitOrder::noTrump();
        $order = RankOrder::poker();

        // Ace of spades does NOT beat 2 of hearts when hearts are led
        expect($so->beats(
            cardOf(Suit::Spades, Rank::Ace),
            cardOf(Suit::Hearts, Rank::Two),
            Suit::Hearts,
            $order
        ))->toBeFalse();
    });

    it('higher card in same non-lead suit does not beat lower card in lead', function () {
        $so = SuitOrder::noTrump();
        $order = RankOrder::poker();

        expect($so->beats(
            cardOf(Suit::Clubs, Rank::Ace),
            cardOf(Suit::Hearts, Rank::Two),
            Suit::Hearts,
            $order
        ))->toBeFalse();
    });
});

describe('beats — following suit', function () {
    it('led-suit card beats off-suit non-trump', function () {
        $so = SuitOrder::noTrump();
        $order = RankOrder::poker();

        expect($so->beats(
            cardOf(Suit::Hearts, Rank::Two),
            cardOf(Suit::Spades, Rank::Ace),
            Suit::Hearts,
            $order
        ))->toBeTrue();
    });
});

describe('beats — edge cases', function () {
    it('equal rank same suit does not beat (tie)', function () {
        $so = SuitOrder::noTrump();
        $order = RankOrder::poker();

        // Impossible in a real single-deck game, but the logic should
        // return false rather than throw.
        expect($so->beats(
            cardOf(Suit::Hearts, Rank::King),
            cardOf(Suit::Hearts, Rank::King),
            Suit::Hearts,
            $order
        ))->toBeFalse();
    });

    it('different non-trump non-lead suits: neither beats', function () {
        $so = SuitOrder::noTrump();
        $order = RankOrder::poker();

        expect($so->beats(
            cardOf(Suit::Clubs, Rank::Ace),
            cardOf(Suit::Diamonds, Rank::Two),
            Suit::Hearts,
            $order
        ))->toBeFalse();
    });
});
