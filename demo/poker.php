<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Games\Poker;
use Likewinter\CardDeck\Games\Poker\PokerHand;

require_once __DIR__ . '/../vendor/autoload.php';

// ─────────────────────────────────────────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────────────────────────────────────────

$playerNames = ['Alice', 'Bob', 'Carol', 'Dave', 'Eve', 'Frank', 'Grace', 'Heidi'];
$numHands = (int) ($argv[1] ?? 3);
$numRounds = (int) ($argv[2] ?? 3);

if ($numHands < 2 || $numHands > 5 || $numRounds < 1) {
    fwrite(STDERR, "Usage: php demo/poker.php [players 2-5] [rounds >= 1]\n");
    exit(1);
}

$players = array_slice($playerNames, 0, $numHands);

// ─────────────────────────────────────────────────────────────────────────────
// ANSI styling (auto-disabled when stdout is not a terminal)
// ─────────────────────────────────────────────────────────────────────────────

$color = stream_isatty(STDOUT);
$RED    = $color ? "\033[31m" : '';
$GREEN  = $color ? "\033[32m" : '';
$YELLOW = $color ? "\033[33m" : '';
$BOLD   = $color ? "\033[1m"  : '';
$DIM    = $color ? "\033[2m"  : '';
$RESET  = $color ? "\033[0m"  : '';

// ─────────────────────────────────────────────────────────────────────────────
// Rendering helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Wrap a card's rank+suit in red ANSI color if the suit is red.
 */
function cardBody(Card $c, string $red, string $reset): string
{
    $body = $s = $c->rank->getSymbol() . $c->suit->getSymbol();
    return $c->suit->getColor() === 'red' ? "{$red}{$body}{$reset}" : $s;
}

/**
 * Render a 5-card PokerHand as three lines of ASCII card art.
 * Cards are width-4 boxes so 10-rank and single-char ranks align.
 */
function renderHand(PokerHand $hand, string $red, string $reset): string
{
    $top = $mid = $bot = '';
    foreach ([...$hand] as $card) {
        $bodyLen = mb_strlen($card->rank->getSymbol() . $card->suit->getSymbol());
        $leftPad = str_repeat(' ', max(0, 3 - $bodyLen));
        $inner = $leftPad . cardBody($card, $red, $reset) . ' ';
        $top .= '┌────┐ ';
        $mid .= "│{$inner}│ ";
        $bot .= '└────┘ ';
    }

    return rtrim($top) . "\n" . rtrim($mid) . "\n" . rtrim($bot);
}

/**
 * Proportional bar chart for the final scoreboard.
 */
function renderBar(int $wins, int $maxWins, int $barMax, string $green, string $dim, string $reset): string
{
    if ($maxWins === 0) {
        return $dim . str_repeat('·', $barMax) . $reset;
    }
    $len = (int) round($wins / $maxWins * $barMax);

    return $green . str_repeat('█', $len) . $reset . $dim . str_repeat('·', $barMax - $len) . $reset;
}

// ─────────────────────────────────────────────────────────────────────────────
// Banner
// ─────────────────────────────────────────────────────────────────────────────

echo "\n";
echo "{$BOLD}  ♠ ♥ ♦ ♣  POKER TOURNAMENT — 5-CARD STUD  ♣ ♦ ♥ ♠{$RESET}\n";
echo "{$DIM}  " . count($players) . " players · {$numRounds} round" . ($numRounds > 1 ? 's' : '') . " · 52-card deck{$RESET}\n";
echo "{$DIM}  " . str_repeat('─', 46) . "{$RESET}\n";

// ─────────────────────────────────────────────────────────────────────────────
// Tournament loop
// ─────────────────────────────────────────────────────────────────────────────

$scores = array_fill(0, $numHands, 0);
$poker = new Poker(numHands: $numHands);

for ($round = 1; $round <= $numRounds; $round++) {
    echo "\n{$DIM}  ── Round {$round} of {$numRounds} " . str_repeat('─', 30) . "{$RESET}\n\n";

    $poker->deal();
    $hands = $poker->hands();
    $winners = $poker->winners();
    $winnerStrings = array_map(fn (PokerHand $w) => (string) $w, $winners);

    // Show each player's hand
    foreach ($hands as $i => $hand) {
        $name = $players[$i];
        $isWinner = in_array((string) $hand, $winnerStrings, true);
        $marker = $isWinner ? "{$YELLOW}★{$RESET} " : '  ';

        echo $marker . "{$BOLD}{$name}{$RESET}\n";
        echo '  ' . str_replace("\n", "\n  ", renderHand($hand, $RED, $RESET)) . "\n";
        echo "  {$DIM}{$hand->handRank->getName()}{$RESET}\n\n";
    }

    // Winner / tie announcement
    if (count($winners) === 1) {
        $winIdx = array_search((string) $winners[0], array_map(fn (PokerHand $h) => (string) $h, $hands), true);
        $scores[$winIdx]++;
        echo "  {$GREEN}{$BOLD}🏆 {$players[$winIdx]} wins{$RESET} with {$BOLD}{$winners[0]->handRank->getName()}{$RESET}\n";
    } else {
        $tiedNames = [];
        foreach ($winners as $w) {
            $idx = array_search((string) $w, array_map(fn (PokerHand $h) => (string) $h, $hands), true);
            $tiedNames[] = $players[$idx];
            $scores[$idx]++;
        }
        echo "  {$YELLOW}{$BOLD}🤝 Tie: " . implode(', ', $tiedNames) . "{$RESET} — {$winners[0]->handRank->getName()}\n";
    }

    if ($round < $numRounds) {
        $poker->reset();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Final scoreboard
// ─────────────────────────────────────────────────────────────────────────────

echo "\n{$DIM}  " . str_repeat('═', 46) . "{$RESET}\n";
echo "{$BOLD}  FINAL SCOREBOARD{$RESET}\n";
echo "{$DIM}  " . str_repeat('═', 46) . "{$RESET}\n\n";

$maxWins = max($scores);
$barMax = 24;
$nameWidth = max(array_map('strlen', $players));

foreach ($players as $i => $name) {
    $wins = $scores[$i];
    $label = $wins === 1 ? '1 win' : "{$wins} wins";
    $bar = renderBar($wins, $maxWins, $barMax, $GREEN, $DIM, $RESET);
    echo "  " . str_pad($name, $nameWidth) . "  {$bar}  {$DIM}{$label}{$RESET}\n";
}

// Champion
$champions = [];
foreach ($scores as $i => $w) {
    if ($w === $maxWins) {
        $champions[] = $players[$i];
    }
}

echo "\n";
if (count($champions) === 1) {
    echo "  {$GREEN}{$BOLD}🏆 Tournament Champion: {$champions[0]}{$RESET}\n";
} else {
    echo "  {$YELLOW}{$BOLD}🤝 Tournament Tie: " . implode(', ', $champions) . "{$RESET}\n";
}
echo "\n";
