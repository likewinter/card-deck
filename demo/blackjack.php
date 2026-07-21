<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Games\Blackjack;
use Likewinter\CardDeck\Games\Blackjack\BlackjackHand;

require_once __DIR__ . '/../vendor/autoload.php';

// ─────────────────────────────────────────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────────────────────────────────────────

$numRounds = (int) ($argv[1] ?? 3);
$numDecks = 6;

// ─────────────────────────────────────────────────────────────────────────────
// ANSI styling
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

function cardBody(Card $c, string $red, string $reset): string
{
    $body = $c->rank->getSymbol() . $c->suit->getSymbol();
    return $c->suit->getColor() === 'red' ? "{$red}{$body}{$reset}" : $body;
}

function renderHand(BlackjackHand $hand, string $red, string $reset): string
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

function handLabel(BlackjackHand $hand): string
{
    $value = $hand->value();
    $soft = $hand->isSoft() ? ' (soft)' : '';

    if ($hand->isBlackjack()) {
        return "BLACKJACK! ({$value})";
    }
    if ($hand->isBust()) {
        return "BUST ({$value})";
    }

    return "{$value}{$soft}";
}

// ─────────────────────────────────────────────────────────────────────────────
// Banner
// ─────────────────────────────────────────────────────────────────────────────

echo "\n";
echo "{$BOLD}  ♠ ♥ ♦ ♣  BLACKJACK — {$numDecks}-DECK SHOE  ♣ ♦ ♥ ♠{$RESET}\n";
echo "{$DIM}  Player vs Dealer · {$numRounds} round" . ($numRounds > 1 ? 's' : '') . " · dealer stands on 17{$RESET}\n";
echo "{$DIM}  " . str_repeat('─', 46) . "{$RESET}\n";

// ─────────────────────────────────────────────────────────────────────────────
// Game loop
// ─────────────────────────────────────────────────────────────────────────────

$wins = 0;
$losses = 0;
$pushes = 0;

$blackjack = new Blackjack(numPlayers: 1, numDecks: $numDecks);

for ($round = 1; $round <= $numRounds; $round++) {
    echo "\n{$DIM}  ── Round {$round} of {$numRounds} " . str_repeat('─', 30) . "{$RESET}\n\n";

    $blackjack->deal();

    // Player plays with basic strategy: hit below 17
    $playerHand = $blackjack->playerCards(0);
    while ($playerHand->value() < 17 && !$playerHand->isBlackjack()) {
        $blackjack->hit(0);
        $playerHand = $blackjack->playerCards(0);
    }

    // Dealer plays
    $blackjack->dealerPlay();

    $playerHand = $blackjack->playerCards(0);
    $dealerHand = $blackjack->dealerCards();
    $result = $blackjack->outcome(0);

    // Show hands
    echo "  {$BOLD}Dealer{$RESET}\n";
    echo '  ' . str_replace("\n", "\n  ", renderHand($dealerHand, $RED, $RESET)) . "\n";
    echo "  {$DIM}" . handLabel($dealerHand) . "{$RESET}\n\n";

    echo "  {$BOLD}Player{$RESET}\n";
    echo '  ' . str_replace("\n", "\n  ", renderHand($playerHand, $RED, $RESET)) . "\n";
    echo "  {$DIM}" . handLabel($playerHand) . "{$RESET}\n\n";

    // Result
    match ($result) {
        'blackjack' => print("  {$GREEN}{$BOLD}🃏 BLACKJACK! Player wins{$RESET}\n"),
        'win'       => print("  {$GREEN}{$BOLD}🏆 Player wins{$RESET}\n"),
        'lose'      => print("  {$RED}{$BOLD}💥 Dealer wins{$RESET}\n"),
        'push'      => print("  {$YELLOW}{$BOLD}🤝 Push (tie){$RESET}\n"),
    };

    match ($result) {
        'blackjack', 'win' => $wins++,
        'lose'             => $losses++,
        'push'             => $pushes++,
    };

    if ($round < $numRounds) {
        $blackjack->reset();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Summary
// ─────────────────────────────────────────────────────────────────────────────

echo "\n{$DIM}  " . str_repeat('═', 46) . "{$RESET}\n";
echo "{$BOLD}  RESULTS{$RESET}\n";
echo "{$DIM}  " . str_repeat('═', 46) . "{$RESET}\n\n";
echo "  {$GREEN}Wins: {$wins}{$RESET}  {$RED}Losses: {$losses}{$RESET}  {$YELLOW}Pushes: {$pushes}{$RESET}\n\n";
