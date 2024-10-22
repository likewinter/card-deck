<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Likewinter\CardDeck\{Card, Card\Suit, Card\Rank, Dealer, Deck, Hand};

enum HandRank: int
{
    case HighCard = 0;
    case OnePair = 1;
    case TwoPair = 2;
    case ThreeOfAKind = 3;
    case Straight = 4;
    case Flush = 5;
    case FullHouse = 6;
    case FourOfAKind = 7;
    case StraightFlush = 8;

    public function name(): string
    {
        return match ($this) {
            self::HighCard => 'High Card',
            self::OnePair => 'One Pair',
            self::TwoPair => 'Two Pair',
            self::ThreeOfAKind => 'Three of a Kind',
            self::Straight => 'Straight',
            self::Flush => 'Flush',
            self::FullHouse => 'Full House',
            self::FourOfAKind => 'Four of a Kind',
            self::StraightFlush => 'Straight Flush',
        };
    }
}

function createDeck(): Deck
{
    static $deck;
    if ($deck instanceof Deck) {
        return clone $deck;
    }

    $deck = new Deck();

    foreach (Suit::cases() as $suit) {
        if ($suit === Suit::Joker) {
            continue;
        }

        foreach (Rank::cases() as $rank) {
            if ($rank === Rank::Joker) {
                continue;
            }
            $deck->add(new Card($suit, $rank));
        }
    }

    return $deck;
}

function createHands(int $num): array
{
    return array_map(fn () => new Hand(), range(0, $num - 1));
}

function countHandRanks(Hand $hand): array
{
    $ranks = array_count_values(array_map(fn ($rank) => $rank->value, $hand->getRanks()));
    sort($ranks);

    return $ranks;
}

function isFourOfAKind(Hand $hand): bool
{
    return max(countHandRanks($hand)) === 4;
}

function isThreeOfAKind(Hand $hand): bool
{
    return max(countHandRanks($hand)) === 3;
}

function isPair(Hand $hand): bool
{
    return max(countHandRanks($hand)) === 2;
}

function isFullHouse(Hand $hand): bool
{
    return countHandRanks($hand) === [2, 3];
}

function isTwoPair(Hand $hand): bool
{
    return countHandRanks($hand) === [1, 2, 2];
}

function isFlush(Hand $hand): bool
{
    $suits = array_map(fn ($suit) => $suit->value, $hand->getSuits());

    return count(array_unique($suits)) === 1;
}

function isStraight(Hand $hand): bool
{
    $ranks = array_map(fn ($rank) => $rank->getSymbol(), $hand->getRanks());
    sort($ranks);

    return match ($ranks) {
        ['A', '2', '3', '4', '5'] => true,
        ['2', '3', '4', '5', '6'] => true,
        ['3', '4', '5', '6', '7'] => true,
        ['4', '5', '6', '7', '8'] => true,
        ['5', '6', '7', '8', '9'] => true,
        ['6', '7', '8', '9', '10'] => true,
        ['7', '8', '9', '10', 'J'] => true,
        ['8', '9', '10', 'J', 'Q'] => true,
        ['9', '10', 'J', 'Q', 'K'] => true,
        ['10', 'J', 'Q', 'K', 'A'] => true,
        default => false,
    };
}

function isStraightFlush(Hand $hand): bool
{
    return isStraight($hand) && isFlush($hand);
}

function handRank(Hand $hand): HandRank
{
    return match (true) {
        isStraightFlush($hand) => HandRank::StraightFlush,
        isFourOfAKind($hand) => HandRank::FourOfAKind,
        isFullHouse($hand) => HandRank::FullHouse,
        isFlush($hand) => HandRank::Flush,
        isStraight($hand) => HandRank::Straight,
        isThreeOfAKind($hand) => HandRank::ThreeOfAKind,
        isTwoPair($hand) => HandRank::TwoPair,
        isPair($hand) => HandRank::OnePair,
        default => HandRank::HighCard,
    };
}

function scoreRank(HandRank $rank): int
{
    // TODO: Implement
    return 0;
}

$dealer = new Dealer(createDeck(), Dealer::DRAW_SEQUENTIAL, createHands(5));
$dealer->drawAll(5);

echo 'First draw', PHP_EOL;
foreach ($dealer->getHands() as $hand) {
    echo implode(' ', $hand->getCards()), ' - ', handRank($hand)->name(), PHP_EOL;
}

array_map(fn (Hand $hand) => $dealer->discardHand($hand), $dealer->getHands());
$dealer->drawAll(5);

echo 'Second draw', PHP_EOL;
foreach ($dealer->getHands() as $hand) {
    echo implode(' ', $hand->getCards()), ' - ', handRank($hand)->name(), PHP_EOL;
}
