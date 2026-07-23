<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Card\{Rank, Suit};
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\SuitOrder;
use Likewinter\CardDeck\Trick;

function trickCard(Suit $suit, Rank $rank): Card
{
    return new Card(suit: $suit, rank: $rank);
}

it('records played cards in order', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 3,
    );

    $trick->play(trickCard(Suit::Hearts, Rank::Two));
    $trick->play(trickCard(Suit::Hearts, Rank::King));
    $trick->play(trickCard(Suit::Spades, Rank::Ace));

    expect($trick->cards())->toHaveCount(3)
        ->and($trick->leadSuit())->toBe(Suit::Hearts);
});

it('sets lead suit from the first card played', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 2,
    );

    expect($trick->leadSuit())->toBeNull();

    $trick->play(trickCard(Suit::Clubs, Rank::Seven));
    expect($trick->leadSuit())->toBe(Suit::Clubs);
});

it('is empty before any play and complete after all players play', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 2,
    );

    expect($trick->isEmpty())->toBeTrue()
        ->and($trick->isComplete())->toBeFalse();

    $trick->play(trickCard(Suit::Hearts, Rank::Two));
    expect($trick->isEmpty())->toBeFalse()
        ->and($trick->isComplete())->toBeFalse();

    $trick->play(trickCard(Suit::Hearts, Rank::Three));
    expect($trick->isComplete())->toBeTrue();
});

it('rejects play after the trick is complete', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 2,
    );
    $trick->play(trickCard(Suit::Hearts, Rank::Two));
    $trick->play(trickCard(Suit::Hearts, Rank::Three));

    $trick->play(trickCard(Suit::Hearts, Rank::Four));
})->throws(\LogicException::class);

it('rejects winner determination on an incomplete trick', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 2,
    );
    $trick->play(trickCard(Suit::Hearts, Rank::Two));
    $trick->winner();
})->throws(\LogicException::class);

it('tracks current player and enforces turn order', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 3,
    );

    expect($trick->currentPlayer())->toBe(0);

    $trick->play(trickCard(Suit::Hearts, Rank::Two));
    expect($trick->currentPlayer())->toBe(1);

    $trick->play(trickCard(Suit::Hearts, Rank::King));
    expect($trick->currentPlayer())->toBe(2);

    $trick->play(trickCard(Suit::Spades, Rank::Ace));
    expect($trick->currentPlayer())->toBe(0);
});

it('starts from a custom starting player', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 3,
        startingPlayer: 2,
    );

    expect($trick->currentPlayer())->toBe(2);

    $trick->play(trickCard(Suit::Hearts, Rank::Two));
    expect($trick->currentPlayer())->toBe(0);
});

it('determines winner — highest card in led suit (no trump)', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 4,
    );

    $trick->play(trickCard(Suit::Hearts, Rank::Two));   // lead (player 0)
    $trick->play(trickCard(Suit::Hearts, Rank::King));  // follows, higher (player 1)
    $trick->play(trickCard(Suit::Spades, Rank::Ace));   // off-suit, ignored (player 2)
    $trick->play(trickCard(Suit::Hearts, Rank::Ten));   // follows, lower than K (player 3)

    expect($trick->winner())->toBe(1);
});

it('determines winner — trump beats non-trump', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::suit(Suit::Spades, RankOrder::poker()),
        numPlayers: 4,
    );

    $trick->play(trickCard(Suit::Hearts, Rank::Ace));   // lead (player 0)
    $trick->play(trickCard(Suit::Hearts, Rank::King));  // follows, higher in lead (player 1)
    $trick->play(trickCard(Suit::Spades, Rank::Two));   // trump! (player 2)
    $trick->play(trickCard(Suit::Hearts, Rank::Queen)); // follows, lower (player 3)

    // Two of trump beats Ace of hearts
    expect($trick->winner())->toBe(2);
});

it('determines winner — higher trump beats lower trump', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::suit(Suit::Spades, RankOrder::poker()),
        numPlayers: 3,
    );

    $trick->play(trickCard(Suit::Spades, Rank::Two));   // low trump leads (player 0)
    $trick->play(trickCard(Suit::Hearts, Rank::Ace));   // off-suit, ignored (player 1)
    $trick->play(trickCard(Suit::Spades, Rank::King));  // higher trump (player 2)

    expect($trick->winner())->toBe(2);
});

it('determines winner — first player wins when no one follows or trumps', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 3,
    );

    $trick->play(trickCard(Suit::Hearts, Rank::Two));   // lead (player 0)
    $trick->play(trickCard(Suit::Clubs, Rank::Ace));    // off-suit (player 1)
    $trick->play(trickCard(Suit::Diamonds, Rank::King));// off-suit (player 2)

    // No one beat the lead, so the leader wins
    expect($trick->winner())->toBe(0);
});

it('clear resets the trick for reuse', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 2,
    );
    $trick->play(trickCard(Suit::Hearts, Rank::Two));
    $trick->play(trickCard(Suit::Hearts, Rank::King));
    expect($trick->isComplete())->toBeTrue();

    $trick->clear();

    expect($trick->isEmpty())->toBeTrue()
        ->and($trick->leadSuit())->toBeNull()
        ->and($trick->isComplete())->toBeFalse()
        ->and($trick->currentPlayer())->toBe(0);
});

it('clear with nextLeader sets who leads the next trick', function () {
    $trick = new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 4,
    );

    $trick->play(trickCard(Suit::Hearts, Rank::Two));
    $trick->play(trickCard(Suit::Hearts, Rank::King));
    $trick->play(trickCard(Suit::Hearts, Rank::Ace));
    $trick->play(trickCard(Suit::Hearts, Rank::Three));

    $winner = $trick->winner();
    expect($winner)->toBe(2); // Ace wins

    $trick->clear(nextLeader: $winner);
    expect($trick->currentPlayer())->toBe(2);
});

it('rejects fewer than 2 players', function () {
    new Trick(
        suitOrder: SuitOrder::noTrump(RankOrder::poker()),
        numPlayers: 1,
    );
})->throws(\InvalidArgumentException::class);
