<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Stack;
use Likewinter\CardDeck\RankOrder;
use Likewinter\CardDeck\Games\Spades;
use Likewinter\CardDeck\Card\{Rank, Suit};

require_once __DIR__ . '/../vendor/autoload.php';

// ─────────────────────────────────────────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────────────────────────────────────────

$numHands = (int) ($argv[1] ?? 2);
$playerNames = ['North', 'East', 'South', 'West'];

// ─────────────────────────────────────────────────────────────────────────────
// ANSI styling
// ─────────────────────────────────────────────────────────────────────────────

$color = stream_isatty(STDOUT);
$RED    = $color ? "\033[31m" : '';
$GREEN  = $color ? "\033[32m" : '';
$YELLOW = $color ? "\033[33m" : '';
$BLUE   = $color ? "\033[34m" : '';
$BOLD   = $color ? "\033[1m"  : '';
$DIM    = $color ? "\033[2m"  : '';
$RESET  = $color ? "\033[0m"  : '';

// ─────────────────────────────────────────────────────────────────────────────
// Simple AI: follow suit high, void → lowest spade, else lowest card
// ─────────────────────────────────────────────────────────────────────────────

$rankOrder = RankOrder::poker();

function chooseCard(int $player, Stack $hand, ?Suit $leadSuit, RankOrder $rankOrder): Card
{
    $cards = [...$hand];

    if ($leadSuit !== null) {
        // Follow suit: play highest card of the led suit
        $suited = array_filter($cards, fn(Card $c) => $c->suit === $leadSuit);
        if (!empty($suited)) {
            usort($suited, fn(Card $a, Card $b) => $rankOrder->compare($b->rank, $a->rank));
            return array_values($suited)[0];
        }
    }

    // Void in led suit (or leading): play lowest spade if available
    $spades = array_filter($cards, fn(Card $c) => $c->suit === Suit::Spades);
    if (!empty($spades) && $leadSuit !== null) {
        usort($spades, fn(Card $a, Card $b) => $rankOrder->compare($a->rank, $b->rank));
        return array_values($spades)[0];
    }

    // Leading or no spades: play highest non-spade, or lowest card
    $nonSpades = array_filter($cards, fn(Card $c) => $c->suit !== Suit::Spades);
    if (!empty($nonSpades)) {
        usort($nonSpades, fn(Card $a, Card $b) => $rankOrder->compare($b->rank, $a->rank));
        return array_values($nonSpades)[0];
    }

    // Only spades left: play lowest
    usort($cards, fn(Card $a, Card $b) => $rankOrder->compare($a->rank, $b->rank));
    return $cards[0];
}

// ─────────────────────────────────────────────────────────────────────────────
// Rendering
// ─────────────────────────────────────────────────────────────────────────────

function cardStr(Card $c, string $red, string $reset): string
{
    $body = $c->rank->getSymbol() . $c->suit->getSymbol();
    return $c->suit->getColor() === 'red' ? "{$red}{$body}{$reset}" : $body;
}

// ─────────────────────────────────────────────────────────────────────────────
// Banner
// ─────────────────────────────────────────────────────────────────────────────

echo "\n";
echo "{$BOLD}  ♠ ♥ ♦ ♣  SPADES — TRICK-TAKING  ♣ ♦ ♥ ♠{$RESET}\n";
echo "{$DIM}  4 players · {$numHands} hand" . ($numHands > 1 ? 's' : '') . " · spades are trump · 13 tricks{$RESET}\n";
echo "{$DIM}  " . str_repeat('─', 46) . "{$RESET}\n";

// ─────────────────────────────────────────────────────────────────────────────
// Game loop
// ─────────────────────────────────────────────────────────────────────────────

$totalScores = array_fill(0, 4, 0);
$spades = new Spades();

for ($h = 1; $h <= $numHands; $h++) {
    echo "\n{$DIM}  ── Hand {$h} of {$numHands} " . str_repeat('─', 30) . "{$RESET}\n\n";

    $spades->deal();

    // Show dealt hands
    for ($p = 0; $p < 4; $p++) {
        $hand = $spades->hand($p);
        $cardStrs = array_map(fn(Card $c) => cardStr($c, $RED, $RESET), [...$hand]);
        echo "  {$BOLD}{$playerNames[$p]}{$RESET}: " . implode(' ', $cardStrs) . "\n";
    }
    echo "\n";

    // Play the hand
    $scores = $spades->playHand(
        fn(int $player, Stack $hand, ?Suit $leadSuit) =>
            chooseCard($player, $hand, $leadSuit, $rankOrder)
    );

    // Show results
    echo "  {$BOLD}Tricks won:{$RESET}\n";
    for ($p = 0; $p < 4; $p++) {
        $totalScores[$p] += $scores[$p];
        $bar = str_repeat('♠', $scores[$p]) . str_repeat('·', 13 - $scores[$p]);
        echo "  {$playerNames[$p]}: {$bar} {$scores[$p]}\n";
    }

    if ($h < $numHands) {
        $spades->reset();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Final standings
// ─────────────────────────────────────────────────────────────────────────────

echo "\n{$DIM}  " . str_repeat('═', 46) . "{$RESET}\n";
echo "{$BOLD}  FINAL STANDINGS{$RESET}\n";
echo "{$DIM}  " . str_repeat('═', 46) . "{$RESET}\n\n";

arsort($totalScores);
$rank = 1;
foreach ($totalScores as $player => $score) {
    $marker = $rank === 1 ? "{$YELLOW}★{$RESET} " : '  ';
    echo "  {$marker}{$BOLD}{$playerNames[$player]}{$RESET}: {$score} tricks\n";
    $rank++;
}
echo "\n";
