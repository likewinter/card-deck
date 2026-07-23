<?php

use Likewinter\CardDeck\CardInPlay;
use Likewinter\CardDeck\Games\Solitaire;
use Likewinter\CardDeck\Card\Suit;

require_once __DIR__ . '/../vendor/autoload.php';

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
// Rendering
// ─────────────────────────────────────────────────────────────────────────────

function cardStr(CardInPlay $c, string $red, string $reset): string
{
    if ($c->isFaceDown()) {
        return '██';
    }
    $card = $c->underlyingCard();
    $body = $card->rank->getSymbol() . $card->suit->getSymbol();
    return $card->suit->getColor() === 'red' ? "{$red}{$body}{$reset}" : $body;
}

function renderTableau(Solitaire $game, string $red, string $reset, string $bold, string $dim, string $resetAll): void
{
    for ($row = 0; $row < 7; $row++) {
        $line = "  ";
        for ($col = 0; $col < 7; $col++) {
            $pile = $game->tableau($col);
            $cards = [...$pile];
            if (isset($cards[$row])) {
                $line .= str_pad(cardStr($cards[$row], $red, $resetAll), 12);
            } else {
                $line .= str_pad('', 12);
            }
        }
        echo rtrim($line) . "\n";
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Banner
// ─────────────────────────────────────────────────────────────────────────────

echo "\n";
echo "{$BOLD}  ♠ ♥ ♦ ♣  KLONDIKE SOLITAIRE  ♣ ♦ ♥ ♠{$RESET}\n";
echo "{$DIM}  " . str_repeat('─', 46) . "{$RESET}\n\n";

// ─────────────────────────────────────────────────────────────────────────────
// Game
// ─────────────────────────────────────────────────────────────────────────────

$game = new Solitaire();

echo "{$BOLD}  Initial deal:{$RESET}\n\n";
renderTableau($game, $RED, $RESET, $BOLD, $DIM, $RESET);
echo "\n";
echo "  Stock: {$game->stock()->count()} cards · Waste: {$game->waste()->count()} cards\n";
echo "  Foundations: ♥ 0  ♦ 0  ♣ 0  ♠ 0\n";

// Draw a few cards
echo "\n{$DIM}  Drawing 3 cards from stock...{$RESET}\n";
for ($i = 0; $i < 3; $i++) {
    $game->drawFromStock();
}

$wasteCards = [...$game->waste()];
$wasteStr = implode(' ', array_map(
    fn($c) => cardStr($c, $RED, $RESET),
    $wasteCards,
));
echo "  Waste: {$wasteStr}\n";
echo "  Stock: {$game->stock()->count()} cards\n";

// Try to move an Ace to foundation if visible
echo "\n{$DIM}  Scanning for Aces to move to foundations...{$RESET}\n";
$moved = 0;
for ($i = 0; $i < 7; $i++) {
    $pile = $game->tableau($i);
    if ($pile->isEmpty()) {
        continue;
    }
    $top = [...$pile->peek()][0];
    $underlying = $top->underlyingCard();
    if ($underlying->rank === \Likewinter\CardDeck\Card\Rank::Ace) {
        $game->moveToFoundation($i, $underlying->suit);
        echo "  {$GREEN}Moved A{$underlying->suit->getSymbol()} from pile " . ($i + 1) . " to foundation{$RESET}\n";
        $moved++;
    }
}
if ($moved === 0) {
    echo "  {$DIM}No Aces exposed in tableau top cards{$RESET}\n";
}

echo "\n{$BOLD}  Current state:{$RESET}\n\n";
renderTableau($game, $RED, $RESET, $BOLD, $DIM, $RESET);
echo "\n";

$fStr = '';
foreach (Suit::casesWithoutJoker() as $suit) {
    $fStr .= "{$suit->getSymbol()} {$game->foundation($suit)->count()}  ";
}
echo "  Stock: {$game->stock()->count()} · Waste: {$game->waste()->count()} · Foundations: {$fStr}\n";
echo "  Won: " . ($game->isWon() ? "{$GREEN}Yes!{$RESET}" : "{$DIM}No{$RESET}") . "\n";
echo "\n";
