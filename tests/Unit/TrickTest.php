<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\SuitOrder;
use Likewinter\CardDeck\Trick;
use Likewinter\CardDeck\Trump;

function trickCard(Suit $suit, Rank $rank): Card
{
    return new Card(suit: $suit, rank: $rank);
}

it('records played cards and players in order', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 3,
    );

    $trick->play(0, trickCard(Suit::Hearts, Rank::Two));
    $trick->play(1, trickCard(Suit::Hearts, Rank::King));
    $trick->play(2, trickCard(Suit::Spades, Rank::Ace));

    expect($trick->cards())->toHaveCount(3)
        ->and($trick->players())->toBe([0, 1, 2])
        ->and($trick->leadSuit())->toBe(Suit::Hearts);
});

it('sets lead suit from the first card played', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 2,
    );

    expect($trick->leadSuit())->toBeNull();

    $trick->play(0, trickCard(Suit::Clubs, Rank::Seven));
    expect($trick->leadSuit())->toBe(Suit::Clubs);
});

it('is empty before any play and complete after all players play', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 2,
    );

    expect($trick->isEmpty())->toBeTrue()
        ->and($trick->isComplete())->toBeFalse();

    $trick->play(0, trickCard(Suit::Hearts, Rank::Two));
    expect($trick->isEmpty())->toBeFalse()
        ->and($trick->isComplete())->toBeFalse();

    $trick->play(1, trickCard(Suit::Hearts, Rank::Three));
    expect($trick->isComplete())->toBeTrue();
});

it('rejects play after the trick is complete', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 2,
    );
    $trick->play(0, trickCard(Suit::Hearts, Rank::Two));
    $trick->play(1, trickCard(Suit::Hearts, Rank::Three));

    $trick->play(0, trickCard(Suit::Hearts, Rank::Four));
})->throws(\LogicException::class);

it('rejects out-of-range player index', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 2,
    );
    $trick->play(5, trickCard(Suit::Hearts, Rank::Two));
})->throws(\InvalidArgumentException::class);

it('rejects winner determination on an incomplete trick', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 2,
    );
    $trick->play(0, trickCard(Suit::Hearts, Rank::Two));
    $trick->winner();
})->throws(\LogicException::class);

it('determines winner — highest card in led suit (no trump)', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 4,
    );

    $trick->play(0, trickCard(Suit::Hearts, Rank::Two));   // lead
    $trick->play(1, trickCard(Suit::Hearts, Rank::King));  // follows, higher
    $trick->play(2, trickCard(Suit::Spades, Rank::Ace));   // off-suit, ignored
    $trick->play(3, trickCard(Suit::Hearts, Rank::Ten));   // follows, lower than K

    expect($trick->winner())->toBe(1);
});

it('determines winner — trump beats non-trump', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::suit(Suit::Spades),
        rankOrder: RankOrder::poker(),
        numPlayers: 4,
    );

    $trick->play(0, trickCard(Suit::Hearts, Rank::Ace));   // lead
    $trick->play(1, trickCard(Suit::Hearts, Rank::King));  // follows, higher in lead
    $trick->play(2, trickCard(Suit::Spades, Rank::Two));   // trump!
    $trick->play(3, trickCard(Suit::Hearts, Rank::Queen)); // follows, lower

    // Two of trump beats Ace of hearts
    expect($trick->winner())->toBe(2);
});

it('determines winner — higher trump beats lower trump', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::suit(Suit::Spades),
        rankOrder: RankOrder::poker(),
        numPlayers: 3,
    );

    $trick->play(0, trickCard(Suit::Spades, Rank::Two));   // low trump leads
    $trick->play(1, trickCard(Suit::Hearts, Rank::Ace));   // off-suit, ignored
    $trick->play(2, trickCard(Suit::Spades, Rank::King));  // higher trump

    expect($trick->winner())->toBe(2);
});

it('determines winner — first player wins when no one follows or trumps', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 3,
    );

    $trick->play(0, trickCard(Suit::Hearts, Rank::Two));   // lead
    $trick->play(1, trickCard(Suit::Clubs, Rank::Ace));    // off-suit
    $trick->play(2, trickCard(Suit::Diamonds, Rank::King));// off-suit

    // No one beat the lead, so the leader wins
    expect($trick->winner())->toBe(0);
});

it('clear resets the trick for reuse', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 2,
    );
    $trick->play(0, trickCard(Suit::Hearts, Rank::Two));
    $trick->play(1, trickCard(Suit::Hearts, Rank::King));
    expect($trick->isComplete())->toBeTrue();

    $trick->clear();

    expect($trick->isEmpty())->toBeTrue()
        ->and($trick->leadSuit())->toBeNull()
        ->and($trick->isComplete())->toBeFalse();
});

it('rejects fewer than 2 players', function () {
    new Trick(
        suitOrder: SuitOrder::noTrump(),
        rankOrder: RankOrder::poker(),
        numPlayers: 1,
    );
})->throws(\InvalidArgumentException::class);
