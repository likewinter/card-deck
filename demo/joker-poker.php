<?php

use Likewinter\CardDeck\Card;
use Likewinter\CardDeck\Wildcard;
use Likewinter\CardDeck\Games\JokerPoker;
use Likewinter\CardDeck\Card\{Rank, Suit};

require_once __DIR__ . '/../vendor/autoload.php';

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
// Card rendering
// ─────────────────────────────────────────────────────────────────────────────

function cardStr($c, string $red, string $reset): string
{
    if ($c instanceof Wildcard) {
        if ($c->isAssigned()) {
            $card = $c->effective();
            $body = $card->rank->getSymbol() . $card->suit->getSymbol();
            $colored = $card->suit->getColor() === 'red' ? "{$red}{$body}{$reset}" : $body;
            return "({$colored})"; // parens = wildcard assignment
        }
        return '🃏';
    }
    $body = $c->rank->getSymbol() . $c->suit->getSymbol();
    return $c->suit->getColor() === 'red' ? "{$red}{$body}{$reset}" : $body;
}

// ─────────────────────────────────────────────────────────────────────────────
// Banner
// ─────────────────────────────────────────────────────────────────────────────

$numHands = max(2, min(4, (int) ($argv[1] ?? 2)));

echo "\n";
echo "{$BOLD}  ♠ ♥ ♦ ♣  JOKER POKER — WILDCARDS  ♣ ♦ ♥ ♠{$RESET}\n";
echo "{$DIM}  {$numHands} players · 54-card deck (2 jokers wild) · 5-card hands{$RESET}\n";
echo "{$DIM}  " . str_repeat('─', 46) . "{$RESET}\n\n";

// ─────────────────────────────────────────────────────────────────────────────
// Game
// ─────────────────────────────────────────────────────────────────────────────

$game = new JokerPoker(numHands: $numHands);
$game->deal();

$playerNames = ['Alice', 'Bob', 'Carol', 'Dave'];

// Show dealt hands
for ($p = 0; $p < $numHands; $p++) {
    $hand = $game->hand($p);
    $cardStrs = array_map(fn($c) => cardStr($c, $RED, $RESET), [...$hand]);
    $wildNote = $game->hasUnassignedWildcards($p) ? " {$YELLOW}← has joker!{$RESET}" : '';
    echo "  {$BOLD}{$playerNames[$p]}{$RESET}: " . implode(' ', $cardStrs) . "{$wildNote}\n";
}

// Auto-assign jokers (simple strategy: complete the best possible hand)
echo "\n{$DIM}  Assigning jokers...{$RESET}\n";
for ($p = 0; $p < $numHands; $p++) {
    while ($game->hasUnassignedWildcards($p)) {
        // Simple: assign to Ace of the most common suit in hand
        $hand = $game->hand($p);
        $suits = [];
        foreach ([...$hand] as $c) {
            if (!$c instanceof Wildcard) {
                $s = $c->underlyingCard()->suit;
                $suits[$s->value] = ($suits[$s->value] ?? 0) + 1;
            }
        }
        arsort($suits);
        $bestSuit = Suit::from(array_key_first($suits) ?? Suit::Hearts->value);

        // Find the highest missing rank in that suit
        $haveRanks = [];
        foreach ([...$hand] as $c) {
            if (!$c instanceof Wildcard) {
                $u = $c->underlyingCard();
                if ($u->suit === $bestSuit) {
                    $haveRanks[] = $u->rank;
                }
            }
        }
        $target = Rank::Ace;
        foreach ([Rank::Ace, Rank::King, Rank::Queen, Rank::Jack, Rank::Ten] as $r) {
            if (!in_array($r, $haveRanks, true)) {
                $target = $r;
                break;
            }
        }

        $game->assignWildcard($p, new Card($bestSuit, $target));
        echo "  {$playerNames[$p]}: joker → {$target->getSymbol()}{$bestSuit->getSymbol()}\n";
    }
}

// Evaluate and show results
echo "\n{$BOLD}  Results:{$RESET}\n";
for ($p = 0; $p < $numHands; $p++) {
    $pokerHand = $game->pokerHand($p);
    $handStr = implode(' ', array_map(fn($c) => cardStr($c, $RED, $RESET), [...$game->hand($p)]));
    echo "  {$playerNames[$p]}: {$handStr}  {$DIM}→{$RESET} {$BOLD}{$pokerHand->handRank->getName()}{$RESET}\n";
}

// Winners
$winners = $game->winners();
$bestRank = $winners[0]->handRank;
$winnerNames = [];
for ($p = 0; $p < $numHands; $p++) {
    $ph = $game->pokerHand($p);
    if ($ph->compare($winners[0]) === 0) {
        $winnerNames[] = $playerNames[$p];
    }
}

echo "\n";
if (count($winnerNames) === 1) {
    echo "  {$GREEN}{$BOLD}★ {$winnerNames[0]} wins with {$bestRank->getName()}!{$RESET}\n";
} else {
    echo "  {$YELLOW}{$BOLD}Tie: " . implode(' & ', $winnerNames) . " with {$bestRank->getName()}{$RESET}\n";
}
echo "\n";
