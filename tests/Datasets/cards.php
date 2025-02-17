<?php

use Likewinter\CardDeck\Card\{Rank, Suit};
use Likewinter\CardDeck\Card;

/**
 * @return list<Card>
 */
function randomCards(int $count = 1): array
{
    $suits = Suit::casesWithoutJoker();
    $ranks = Rank::casesWithoutJoker();

    return array_map(fn() => new Card(suit: $suits[array_rand($suits)], rank: $ranks[array_rand($ranks)]), range(1, $count));
}

dataset('two random cards', function () {
    return [
        'two random cards' => [randomCards(2)],
    ];
});

dataset('five random cards', function () {
    return [
        'five random cards' => [randomCards(5)],
    ];
});

dataset('cards and their string representations', function () {
    return [
        'ace of clubs' => [Suit::Clubs, Rank::Ace, 'A♣'],
        'two of diamonds' => [Suit::Diamonds, Rank::Two, '2♦'],
        'three of hearts' => [Suit::Hearts, Rank::Three, '3♥'],
        'four of spades' => [Suit::Spades, Rank::Four, '4♠'],
        'ten of hearts' => [Suit::Hearts, Rank::Ten, '10♥'],
        'jack of clubs' => [Suit::Clubs, Rank::Jack, 'J♣'],
        'queen of diamonds' => [Suit::Diamonds, Rank::Queen, 'Q♦'],
        'king of spades' => [Suit::Spades, Rank::King, 'K♠'],
        'joker' => [Suit::Joker, Rank::Joker, '🃏🃏'],
    ];
});

dataset('invalid joker cards', function () {
    return [
        'joker with non-joker rank' => [Suit::Joker, Rank::Ace],
        'joker with non-joker suit' => [Suit::Clubs, Rank::Joker],
    ];
});

dataset('cards to compare', function () {
    return [
        'higher rank' => [Card::fromString('J♥'), Card::fromString('10♥'), true, false],
        'equal rank' => [Card::fromString('J♥'), Card::fromString('J♥'), false, true],
        'lower rank' => [Card::fromString('2♥'), Card::fromString('J♥'), false, false],
    ];
});

dataset('cards with their string representations', function () {
    return [
        'A♣, 2♦, 3♥, 4♠' => [
            [
                new Card(suit: Suit::Clubs, rank: Rank::Ace),
                new Card(suit: Suit::Diamonds, rank: Rank::Two),
                new Card(suit: Suit::Hearts, rank: Rank::Three),
                new Card(suit: Suit::Spades, rank: Rank::Four),
            ],
            'A♣,2♦,3♥,4♠',
        ],
    ];
});

dataset('cards to find within', function () {
    $cards = randomCards(5);
    $cardsToFind = array_slice($cards, 0, 3);

    return [
        '1 card, 1 to find' => [[$cards[0]], [$cards[0]]],
        '5 cards, 3 to find' => [$cards, $cardsToFind],
        '5 cards, 5 to find' => [$cards, $cards],
    ];
});

dataset('cards not in stack', function () {
    $cards = [
        new Card(suit: Suit::Clubs, rank: Rank::Ace),
        new Card(suit: Suit::Diamonds, rank: Rank::Two),
        new Card(suit: Suit::Hearts, rank: Rank::Three),
        new Card(suit: Suit::Spades, rank: Rank::Four),
    ];
    $cardsNotInStack = [
        new Card(suit: Suit::Spades, rank: Rank::Five),
        new Card(suit: Suit::Clubs, rank: Rank::Six),
        new Card(suit: Suit::Hearts, rank: Rank::Seven),
    ];

    return [
        '5 cards, 3 not in stack' => [$cards, $cardsNotInStack],
    ];
});
