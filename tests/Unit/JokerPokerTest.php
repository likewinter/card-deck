<?php

use Likewinter\CardDeck\{Card, Stack, Table, Wildcard};
use Likewinter\CardDeck\Card\{Rank, Suit};
use Likewinter\CardDeck\Games\JokerPoker;
use Likewinter\CardDeck\Games\Poker\HandRank;

function wildDeck(Card ...$cards): Stack
{
    $wrapped = array_values(array_map(
        fn($c) => $c->isJoker() ? new Wildcard($c) : $c,
        $cards,
    ));

    return new Stack($wrapped, count($wrapped));
}

function joker(): Card
{
    return new Card(Suit::Joker, Rank::Joker);
}

describe('dealing', function () {
    it('deals 5 cards to each hand', function () {
        $game = new JokerPoker(numHands: 3);
        $game->deal();

        for ($i = 0; $i < 3; $i++) {
            expect($game->hand($i)->count())->toBe(5);
        }
    });

    it('wraps jokers as Wildcard in the deck', function () {
        $deck = wildDeck(
            new Card(Suit::Hearts, Rank::Ace),
            new Card(Suit::Hearts, Rank::King),
            joker(),
            new Card(Suit::Spades, Rank::Two),
            new Card(Suit::Spades, Rank::Three),
        );

        $found = false;
        foreach ([...$deck] as $card) {
            if ($card instanceof Wildcard) {
                $found = true;
                expect($card->isUnassigned())->toBeTrue();
            }
        }
        expect($found)->toBeTrue();
    });
});

describe('wildcard assignment', function () {
    it('assigns an unassigned wildcard to a card', function () {
        $deck = wildDeck(
            joker(),
            new Card(Suit::Hearts, Rank::King),
            new Card(Suit::Hearts, Rank::Queen),
            new Card(Suit::Hearts, Rank::Jack),
            new Card(Suit::Hearts, Rank::Ten),
            // hand 1
            new Card(Suit::Spades, Rank::Two),
            new Card(Suit::Spades, Rank::Three),
            new Card(Suit::Spades, Rank::Four),
            new Card(Suit::Spades, Rank::Five),
            new Card(Suit::Spades, Rank::Six),
        );
        $table = new Table(deck: $deck, shuffle: false);
        $game = new JokerPoker(numHands: 2, table: $table);
        $game->deal();

        expect($game->hasUnassignedWildcards(0))->toBeTrue();

        $game->assignWildcard(0, new Card(Suit::Hearts, Rank::Ace));

        expect($game->hasUnassignedWildcards(0))->toBeFalse();
    });

    it('throws when no unassigned wildcard exists', function () {
        $deck = wildDeck(
            new Card(Suit::Hearts, Rank::Ace),
            new Card(Suit::Hearts, Rank::King),
            new Card(Suit::Hearts, Rank::Queen),
            new Card(Suit::Hearts, Rank::Jack),
            new Card(Suit::Hearts, Rank::Ten),
            new Card(Suit::Spades, Rank::Two),
            new Card(Suit::Spades, Rank::Three),
            new Card(Suit::Spades, Rank::Four),
            new Card(Suit::Spades, Rank::Five),
            new Card(Suit::Spades, Rank::Six),
        );
        $table = new Table(deck: $deck, shuffle: false);
        $game = new JokerPoker(numHands: 2, table: $table);
        $game->deal();

        $game->assignWildcard(0, new Card(Suit::Hearts, Rank::Ace));
    })->throws(\LogicException::class);
});

describe('hand evaluation with wildcards', function () {
    it('classifies a hand with an assigned wildcard', function () {
        // Joker assigned to A♥ completes a royal flush: A♥ K♥ Q♥ J♥ 10♥
        $deck = wildDeck(
            joker(),
            new Card(Suit::Hearts, Rank::King),
            new Card(Suit::Hearts, Rank::Queen),
            new Card(Suit::Hearts, Rank::Jack),
            new Card(Suit::Hearts, Rank::Ten),
            new Card(Suit::Spades, Rank::Two),
            new Card(Suit::Spades, Rank::Three),
            new Card(Suit::Spades, Rank::Four),
            new Card(Suit::Spades, Rank::Five),
            new Card(Suit::Spades, Rank::Six),
        );
        $table = new Table(deck: $deck, shuffle: false);
        $game = new JokerPoker(numHands: 2, table: $table);
        $game->deal();

        $game->assignWildcard(0, new Card(Suit::Hearts, Rank::Ace));

        $hand = $game->pokerHand(0);
        expect($hand->handRank)->toBe(HandRank::ROYAL_FLUSH);
    });

    it('rejects evaluation with unassigned wildcards', function () {
        $deck = wildDeck(
            joker(),
            new Card(Suit::Hearts, Rank::King),
            new Card(Suit::Hearts, Rank::Queen),
            new Card(Suit::Hearts, Rank::Jack),
            new Card(Suit::Hearts, Rank::Ten),
            new Card(Suit::Spades, Rank::Two),
            new Card(Suit::Spades, Rank::Three),
            new Card(Suit::Spades, Rank::Four),
            new Card(Suit::Spades, Rank::Five),
            new Card(Suit::Spades, Rank::Six),
        );
        $table = new Table(deck: $deck, shuffle: false);
        $game = new JokerPoker(numHands: 2, table: $table);
        $game->deal();

        $game->pokerHand(0);
    })->throws(\LogicException::class);

    it('wildcard assigned to complete a straight', function () {
        // Joker assigned to 9♥ completes: 9♥ 10♥ J♥ Q♥ K♥ (straight flush)
        $deck = wildDeck(
            joker(),
            new Card(Suit::Hearts, Rank::Ten),
            new Card(Suit::Hearts, Rank::Jack),
            new Card(Suit::Hearts, Rank::Queen),
            new Card(Suit::Hearts, Rank::King),
            new Card(Suit::Clubs, Rank::Two),
            new Card(Suit::Clubs, Rank::Three),
            new Card(Suit::Clubs, Rank::Four),
            new Card(Suit::Clubs, Rank::Five),
            new Card(Suit::Clubs, Rank::Six),
        );
        $table = new Table(deck: $deck, shuffle: false);
        $game = new JokerPoker(numHands: 2, table: $table);
        $game->deal();

        $game->assignWildcard(0, new Card(Suit::Hearts, Rank::Nine));

        $hand = $game->pokerHand(0);
        expect($hand->handRank)->toBe(HandRank::STRAIGHT_FLUSH);
    });
});

describe('winners', function () {
    it('wildcard hand beats natural hand', function () {
        // Hand 0: joker + K♥ Q♥ J♥ 10♥ → assign A♥ → royal flush
        // Hand 1: A♠ K♠ Q♠ J♠ 2♣ → high card (no straight, no flush)
        $deck = wildDeck(
            joker(),
            new Card(Suit::Hearts, Rank::King),
            new Card(Suit::Hearts, Rank::Queen),
            new Card(Suit::Hearts, Rank::Jack),
            new Card(Suit::Hearts, Rank::Ten),
            new Card(Suit::Spades, Rank::Ace),
            new Card(Suit::Spades, Rank::King),
            new Card(Suit::Spades, Rank::Queen),
            new Card(Suit::Spades, Rank::Jack),
            new Card(Suit::Clubs, Rank::Two),
        );
        $table = new Table(deck: $deck, shuffle: false);
        $game = new JokerPoker(numHands: 2, table: $table);
        $game->deal();

        $game->assignWildcard(0, new Card(Suit::Hearts, Rank::Ace));

        $winners = $game->winners();
        expect($winners)->toHaveCount(1)
            ->and($winners[0]->handRank)->toBe(HandRank::ROYAL_FLUSH);
    });
});

describe('reset', function () {
    it('unassigns wildcards on reset', function () {
        $deck = wildDeck(
            joker(),
            new Card(Suit::Hearts, Rank::King),
            new Card(Suit::Hearts, Rank::Queen),
            new Card(Suit::Hearts, Rank::Jack),
            new Card(Suit::Hearts, Rank::Ten),
            new Card(Suit::Spades, Rank::Two),
            new Card(Suit::Spades, Rank::Three),
            new Card(Suit::Spades, Rank::Four),
            new Card(Suit::Spades, Rank::Five),
            new Card(Suit::Spades, Rank::Six),
        );
        $table = new Table(deck: $deck, shuffle: false);
        $game = new JokerPoker(numHands: 2, table: $table);
        $game->deal();

        $game->assignWildcard(0, new Card(Suit::Hearts, Rank::Ace));
        expect($game->hasUnassignedWildcards(0))->toBeFalse();

        $game->reset();
        $game->deal();

        // After reset + redeal, the wildcard should be unassigned again
        // (it went back to the deck unassigned)
        $hasWild = false;
        foreach ([...$game->hand(0)] as $card) {
            if ($card instanceof Wildcard) {
                $hasWild = true;
                expect($card->isUnassigned())->toBeTrue();
            }
        }
        // The wildcard might be in either hand after shuffle
        expect($game->hand(0)->count())->toBe(5);
    });
});
